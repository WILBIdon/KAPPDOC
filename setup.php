<?php
/**
 * setup.php - Generador automático de estructura multicliente
 * 
 * Ejecutar: php setup.php
 * 
 * Este script:
 * 1. Lee config.json
 * 2. Crea carpetas para cada cliente
 * 3. Copia archivos HTML y personaliza
 * 4. Crea carpetas de uploads
 */

echo "=== SETUP MULTICLIENTE ===\n\n";

// Verificar que existe config.json
if (!file_exists(__DIR__ . '/config.json')) {
    die("❌ Error: No se encontró config.json\n");
}

// Cargar configuración
$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
if (!$config || !isset($config['clientes'])) {
    die("❌ Error: config.json mal formado\n");
}

echo "✅ Configuración cargada correctamente\n";
echo "📋 Clientes encontrados: " . count($config['clientes']) . "\n\n";

// Plantillas
$adminTemplate = __DIR__ . '/admin/index.html';
$publicoTemplate = __DIR__ . '/bc/index.html';

if (!file_exists($adminTemplate)) {
    die("❌ Error: No se encontró /admin/index.html\n");
}
if (!file_exists($publicoTemplate)) {
    die("❌ Error: No se encontró /bc/index.html\n");
}

// Procesar cada cliente
foreach ($config['clientes'] as $slug => $cliente) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔧 Configurando cliente: {$cliente['nombre']} ({$slug})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // 1. Crear carpeta admin
    $adminDir = __DIR__ . "/admin/{$slug}";
    if (!is_dir($adminDir)) {
        mkdir($adminDir, 0755, true);
        echo "  ✅ Carpeta admin creada: /admin/{$slug}/\n";
    } else {
        echo "  ℹ️  Carpeta admin ya existe: /admin/{$slug}/\n";
    }
    
    // 2. Copiar admin HTML
    $adminDest = $adminDir . '/index.html';
    copy($adminTemplate, $adminDest);
    echo "  ✅ Admin HTML copiado\n";
    
    // 3. Crear carpeta pública
    $publicoDir = __DIR__ . "/bc/{$slug}";
    if (!is_dir($publicoDir)) {
        mkdir($publicoDir, 0755, true);
        echo "  ✅ Carpeta pública creada: /bc/{$slug}/\n";
    } else {
        echo "  ℹ️  Carpeta pública ya existe: /bc/{$slug}/\n";
    }
    
    // 4. Copiar público HTML
    $publicoDest = $publicoDir . '/index.html';
    copy($publicoTemplate, $publicoDest);
    echo "  ✅ Público HTML copiado\n";
    
    // 5. Copiar logo si existe
    if (isset($cliente['publico']['logo']) && file_exists(__DIR__ . '/' . $cliente['publico']['logo'])) {
        $logoSrc = __DIR__ . '/' . $cliente['publico']['logo'];
        $logoDest = $publicoDir . '/' . basename($cliente['publico']['logo']);
        copy($logoSrc, $logoDest);
        echo "  ✅ Logo copiado: {$cliente['publico']['logo']}\n";
    }
    
    // 6. Crear carpeta uploads
    $uploadsDir = __DIR__ . "/uploads/{$slug}";
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        echo "  ✅ Carpeta uploads creada: /uploads/{$slug}/\n";
    } else {
        echo "  ℹ️  Carpeta uploads ya existe: /uploads/{$slug}/\n";
    }
    
    // 7. Crear .htaccess para proteger uploads (opcional)
    $htaccess = $uploadsDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Options -Indexes\n");
        echo "  ✅ .htaccess creado en uploads\n";
    }
    
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✨ SETUP COMPLETADO ✨\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📌 PRÓXIMOS PASOS:\n\n";
echo "1. Ejecutar el SQL en tu base de datos:\n";
echo "   mysql -h sql200.infinityfree.com -u if0_39064130 -p if0_39064130_buscador < update_database.sql\n\n";

echo "2. Acceder a los paneles admin:\n";
foreach ($config['clientes'] as $slug => $cliente) {
    echo "   • {$cliente['nombre']}: https://tudominio.com/admin/{$slug}/\n";
    echo "     Clave de acceso: {$cliente['admin']['clave_acceso']}\n";
    echo "     Clave de borrado: {$cliente['admin']['clave_borrado']}\n\n";
}

echo "3. Acceder a los portales públicos:\n";
foreach ($config['clientes'] as $slug => $cliente) {
    echo "   • {$cliente['nombre']}: https://tudominio.com/bc/{$slug}/\n";
}

echo "\n";
echo "4. Configurar QR codes (opcional):\n";
echo "   Editar: /qr/redirects.json\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 ¡Todo listo! 🎉\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
?>