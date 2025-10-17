<?php 
// api.php - Sistema Multicliente

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// —————————————————————————————
// Cargar configuración
// —————————————————————————————
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    echo json_encode(['error' => 'Archivo de configuración no encontrado']);
    exit;
}
$config = json_decode(file_get_contents($configFile), true);

// —————————————————————————————
// Conexión a la base de datos
// —————————————————————————————
$db_config = $config['database'];
$dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset=utf8";
try {
    $db = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: '.$e->getMessage()]);
    exit;
}

// —————————————————————————————
// Obtener cliente desde parámetro
// —————————————————————————————
$cliente_slug = $_REQUEST['cliente'] ?? '';
if (!$cliente_slug || !isset($config['clientes'][$cliente_slug])) {
    echo json_encode(['error' => 'Cliente no válido']);
    exit;
}

$cliente = $config['clientes'][$cliente_slug];
$action = $_REQUEST['action'] ?? '';

// —————————————————————————————
// Helper: Directorio de uploads
// —————————————————————————————
function getUploadsDir($slug) {
    $dir = __DIR__ . '/uploads/' . $slug;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

// —————————————————————————————
// Acciones
// —————————————————————————————
switch ($action) {

  // —— GET CONFIG ——
  case 'get_config':
    echo json_encode($cliente);
    break;

  // —— AUTOCOMPLETE SUGGEST ——  
  case 'suggest':
    $term = trim($_GET['term'] ?? '');
    if ($term === '') {
      echo json_encode([]);
      exit;
    }
    $stmt = $db->prepare("
      SELECT DISTINCT code 
      FROM codes 
      WHERE cliente = ? AND code LIKE ? 
      ORDER BY code ASC 
      LIMIT 10
    ");
    $stmt->execute([$cliente_slug, $term . '%']);
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($codes);
    break;

  // —— SUBIR NUEVO DOCUMENTO ——  
  case 'upload':
    $name  = $_POST['name'];
    $date  = $_POST['date'];
    $codes = array_filter(array_map('trim', preg_split('/\r?\n/', $_POST['codes'] ?? '')));
    $file  = $_FILES['file'];
    
    $uploadsDir = getUploadsDir($cliente_slug);
    $filename = time().'_'.basename($file['name']);
    
    if (!move_uploaded_file($file['tmp_name'], $uploadsDir.'/'.$filename)) {
      echo json_encode(['error'=>'No se pudo subir el archivo']);
      exit;
    }
    
    $db->prepare('INSERT INTO documents (cliente, name, date, path) VALUES (?,?,?,?)')
       ->execute([$cliente_slug, $name, $date, $filename]);
    $docId = $db->lastInsertId();
    
    $ins = $db->prepare('INSERT INTO codes (cliente, document_id, code) VALUES (?,?,?)');
    foreach (array_unique($codes) as $c) {
      $ins->execute([$cliente_slug, $docId, $c]);
    }
    echo json_encode(['message'=>'Documento guardado']);
    break;

  // —— LISTAR CON PAGINACIÓN ——  
  case 'list':
    $page    = max(1,(int)($_GET['page'] ?? 1));
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
    
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM documents WHERE cliente = ?");
    $totalStmt->execute([$cliente_slug]);
    $total = (int)$totalStmt->fetchColumn();

    if ($perPage === 0) {
      $stmt = $db->prepare("
        SELECT d.id,d.name,d.date,d.path,
               GROUP_CONCAT(c.code SEPARATOR '\n') AS codes
        FROM documents d
        LEFT JOIN codes c ON d.id=c.document_id AND c.cliente = ?
        WHERE d.cliente = ?
        GROUP BY d.id
        ORDER BY d.date DESC
      ");
      $stmt->execute([$cliente_slug, $cliente_slug]);
      $rows = $stmt->fetchAll();
      $lastPage = 1;
      $page = 1;
    } else {
      $perPage = max(1, min(50, $perPage));
      $offset  = ($page - 1) * $perPage;
      $lastPage = (int)ceil($total / $perPage);

      $stmt = $db->prepare("
        SELECT d.id,d.name,d.date,d.path,
               GROUP_CONCAT(c.code SEPARATOR '\n') AS codes
        FROM documents d
        LEFT JOIN codes c ON d.id=c.document_id AND c.cliente = ?
        WHERE d.cliente = ?
        GROUP BY d.id
        ORDER BY d.date DESC
        LIMIT :l OFFSET :o
      ");
      $stmt->bindValue(':l',$perPage,PDO::PARAM_INT);
      $stmt->bindValue(':o',$offset ,PDO::PARAM_INT);
      $stmt->execute([$cliente_slug, $cliente_slug]);
      $rows = $stmt->fetchAll();
    }

    $docs = array_map(function($r){
      return [
        'id'    => (int)$r['id'],
        'name'  => $r['name'],
        'date'  => $r['date'],
        'path'  => $r['path'],
        'codes' => $r['codes'] ? explode("\n",$r['codes']) : []
      ];
    }, $rows);

    echo json_encode([
      'total'     => $total,
      'page'      => $page,
      'per_page'  => $perPage,
      'last_page' => $lastPage,
      'data'      => $docs
    ]);
    break;

  // —— BÚSQUEDA INTELIGENTE VORAZ ——  
  case 'search':
    $codes = array_filter(array_map('trim', preg_split('/\r?\n/', $_POST['codes'] ?? '')));
    if (empty($codes)) {
      echo json_encode([]);
      exit;
    }

    $cond = implode(" OR ", array_fill(0, count($codes), "UPPER(c.code) = UPPER(?)"));
    $params = array_merge([$cliente_slug, $cliente_slug], $codes);
    
    $stmt = $db->prepare("
      SELECT d.id,d.name,d.date,d.path,c.code
      FROM documents d
      JOIN codes c ON d.id=c.document_id
      WHERE d.cliente = ? AND c.cliente = ? AND ($cond)
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $docs = [];
    foreach ($rows as $r) {
      $id = (int)$r['id'];
      if (!isset($docs[$id])) {
        $docs[$id] = [
          'id'    => $id,
          'name'  => $r['name'],
          'date'  => $r['date'],
          'path'  => $r['path'],
          'codes' => []
        ];
      }
      if (!in_array($r['code'], $docs[$id]['codes'], true)) {
        $docs[$id]['codes'][] = $r['code'];
      }
    }

    // Algoritmo voraz
    $remaining = $codes;
    $selected  = [];
    while ($remaining) {
      $best      = null;
      $bestCover = [];
      foreach ($docs as $d) {
        $cover = array_intersect($d['codes'], $remaining);
        if (!$best
            || count($cover) > count($bestCover)
            || (count($cover) === count($bestCover) && $d['date'] > $best['date'])
        ) {
          $best      = $d;
          $bestCover = $cover;
        }
      }
      if (!$best || empty($bestCover)) break;
      $selected[] = $best;
      $remaining = array_diff($remaining, $bestCover);
      unset($docs[$best['id']]);
    }

    echo json_encode(array_values($selected));
    break;

  // —— DESCARGAR TODOS LOS PDFS EN ZIP ——  
  case 'download_pdfs':
    $uploadsDir = getUploadsDir($cliente_slug);
    if (!is_dir($uploadsDir)) {
      echo json_encode(['error'=>'Carpeta uploads no encontrada']);
      exit;
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$cliente_slug.'_uploads_'.date('Ymd_His').'.zip"');

    $tmpFile = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) {
      echo json_encode(['error'=>'No se pudo crear el ZIP']);
      exit;
    }

    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($uploadsDir),
      RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $file) {
      if (!$file->isDir()) {
        $filePath     = $file->getRealPath();
        $relativePath = substr($filePath, strlen($uploadsDir) + 1);
        $zip->addFile($filePath, $relativePath);
      }
    }
    $zip->close();

    readfile($tmpFile);
    unlink($tmpFile);
    exit;

  // —— EDITAR DOCUMENTO ——  
  case 'edit':
    $id   = (int)$_POST['id'];
    $name = $_POST['name'];
    $date = $_POST['date'];
    $codes= array_filter(array_map('trim', preg_split('/\r?\n/', $_POST['codes'] ?? '')));
    
    if (!empty($_FILES['file']['tmp_name'])) {
      $old = $db->prepare('SELECT path FROM documents WHERE id=? AND cliente=?');
      $old->execute([$id, $cliente_slug]);
      $oldPath = $old->fetchColumn();
      if ($oldPath) {
        @unlink(getUploadsDir($cliente_slug).'/'.$oldPath);
      }
      
      $fn = time().'_'.basename($_FILES['file']['name']);
      move_uploaded_file($_FILES['file']['tmp_name'], getUploadsDir($cliente_slug).'/'.$fn);
      
      $db->prepare('UPDATE documents SET name=?,date=?,path=? WHERE id=? AND cliente=?')
         ->execute([$name,$date,$fn,$id,$cliente_slug]);
    } else {
      $db->prepare('UPDATE documents SET name=?,date=? WHERE id=? AND cliente=?')
         ->execute([$name,$date,$id,$cliente_slug]);
    }
    
    $db->prepare('DELETE FROM codes WHERE document_id=? AND cliente=?')
       ->execute([$id, $cliente_slug]);
    
    $ins = $db->prepare('INSERT INTO codes (cliente, document_id, code) VALUES (?,?,?)');
    foreach (array_unique($codes) as $c) {
      $ins->execute([$cliente_slug, $id, $c]);
    }
    echo json_encode(['message'=>'Documento actualizado']);
    break;

  // —— ELIMINAR DOCUMENTO ——  
  case 'delete':
    $id = (int)($_GET['id'] ?? 0);
    $old = $db->prepare('SELECT path FROM documents WHERE id=? AND cliente=?');
    $old->execute([$id, $cliente_slug]);
    $oldPath = $old->fetchColumn();
    if ($oldPath) {
      @unlink(getUploadsDir($cliente_slug).'/'.$oldPath);
    }
    
    $db->prepare('DELETE FROM codes WHERE document_id=? AND cliente=?')
       ->execute([$id, $cliente_slug]);
    $db->prepare('DELETE FROM documents WHERE id=? AND cliente=?')
       ->execute([$id, $cliente_slug]);
    echo json_encode(['message'=>'Documento eliminado']);
    break;

  // —— BÚSQUEDA POR CÓDIGO ——  
  case 'search_by_code':
    $code = trim($_POST['code'] ?? $_GET['code'] ?? '');
    if (!$code) {
      echo json_encode([]);
      exit;
    }

    $stmt = $db->prepare("
      SELECT d.id, d.name, d.date, d.path, GROUP_CONCAT(c2.code SEPARATOR '\n') AS codes
      FROM documents d
      JOIN codes c1 ON d.id = c1.document_id AND c1.cliente = ?
      LEFT JOIN codes c2 ON d.id = c2.document_id AND c2.cliente = ?
      WHERE d.cliente = ? AND UPPER(c1.code) = UPPER(?)
      GROUP BY d.id
    ");
    $stmt->execute([$cliente_slug, $cliente_slug, $cliente_slug, $code]);
    $rows = $stmt->fetchAll();

    $docs = array_map(function($r){
      return [
        'id'    => (int)$r['id'],
        'name'  => $r['name'],
        'date'  => $r['date'],
        'path'  => $r['path'],
        'codes' => $r['codes'] ? explode("\n", $r['codes']) : []
      ];
    }, $rows);

    echo json_encode($docs);
    break;

  default:
    echo json_encode(['error'=>'Acción inválida']);
    break;
}
?>