# 📡 Documentación de API Multicliente

Todas las peticiones requieren el parámetro `cliente` para identificar el cliente.

---

## 🔗 Endpoint Base

```
POST/GET https://tudominio.com/api.php
```

---

## 🔐 Autenticación

No hay autenticación de API. La seguridad está en:
- **Claves de acceso** (admin panel)
- **Claves de borrado** (eliminar documentos)
- **Aislamiento por cliente** (backend)

---

## 📋 Acciones Disponibles

### 1. **get_config** - Obtener configuración del cliente

Obtiene toda la configuración del cliente (nombre, textos, claves, etc.)

```http
GET /api.php?action=get_config&cliente=kino
```

**Respuesta exitosa:**
```json
{
  "nombre": "KINO COMPANY SAS",
  "slug": "kino",
  "admin": {
    "clave_acceso": "565",
    "clave_borrado": "0101",
    "titulo": "KINO COMPANY SAS V1"
  },
  "publico": {
    "logo": "Logo-Kino-KB.png",
    "titulo": "KINO COMPANY SAS",
    "descripcion": "...",
    "instrucciones": {...},
    "footer": {...},
    "aviso_legal": "..."
  }
}
```

---

### 2. **suggest** - Autocompletado de códigos

Devuelve códigos que coinciden con el término de búsqueda.

```http
GET /api.php?action=suggest&cliente=kino&term=ABC
```

**Parámetros:**
- `cliente` (requerido): Slug del cliente
- `term` (requerido): Término de búsqueda (mínimo 1 carácter)

**Respuesta:**
```json
["ABC123", "ABC456", "ABC789"]
```

---

### 3. **upload** - Subir nuevo documento

Sube un documento PDF con códigos asociados.

```http
POST /api.php
```

**Parámetros (FormData):**
- `action=upload`
- `cliente` (requerido): Slug del cliente
- `name` (requerido): Nombre del documento
- `date` (requerido): Fecha (formato YYYY-MM-DD)
- `codes` (requerido): Códigos separados por saltos de línea
- `file` (requerido): Archivo PDF (max 10 MB)

**Respuesta exitosa:**
```json
{
  "message": "Documento guardado"
}
```

**Respuesta de error:**
```json
{
  "error": "No se pudo subir el archivo"
}
```

---

### 4. **list** - Listar documentos

Lista todos los documentos del cliente con paginación opcional.

```http
GET /api.php?action=list&cliente=kino&page=1&per_page=50
```

**Parámetros:**
- `cliente` (requerido): Slug del cliente
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Documentos por página (default: 50, 0 = todos)

**Respuesta:**
```json
{
  "total": 150,
  "page": 1,
  "per_page": 50,
  "last_page": 3,
  "data": [
    {
      "id": 1,
      "name": "Declaración 001",
      "date": "2025-01-15",
      "path": "1736966400_doc.pdf",
      "codes": ["ABC123", "XYZ789"]
    }
  ]
}
```

---

### 5. **search** - Búsqueda inteligente (algoritmo voraz)

Busca documentos que contengan los códigos especificados usando algoritmo voraz.

```http
POST /api.php
```

**Parámetros (FormData):**
- `action=search`
- `cliente` (requerido): Slug del cliente
- `codes` (requerido): Códigos separados por saltos de línea

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "Declaración 001",
    "date": "2025-01-15",
    "path": "1736966400_doc.pdf",
    "codes": ["ABC123", "XYZ789", "DEF456"]
  },
  {
    "id": 5,
    "name": "Declaración 005",
    "date": "2025-01-20",
    "path": "1737379200_doc.pdf",
    "codes": ["GHI999"]
  }
]
```

**Algoritmo:**
- Selecciona el documento que cubre más códigos restantes
- Si hay empate, selecciona el más reciente
- Continúa hasta cubrir todos los códigos o no encontrar más

---

### 6. **search_by_code** - Búsqueda por código individual

Busca documentos que contengan un código específico.

```http
POST /api.php
GET /api.php?action=search_by_code&cliente=kino&code=ABC123
```

**Parámetros:**
- `action=search_by_code`
- `cliente` (requerido): Slug del cliente
- `code` (requerido): Código a buscar (case-insensitive)

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "Declaración 001",
    "date": "2025-01-15",
    "path": "1736966400_doc.pdf",
    "codes": ["ABC123", "XYZ789"]
  }
]
```

---

### 7. **edit** - Editar documento

Actualiza un documento existente.

```http
POST /api.php
```

**Parámetros (FormData):**
- `action=edit`
- `cliente` (requerido): Slug del cliente
- `id` (requerido): ID del documento
- `name` (requerido): Nuevo nombre
- `date` (requerido): Nueva fecha
- `codes` (requerido): Nuevos códigos
- `file` (opcional): Nuevo archivo PDF

**Respuesta:**
```json
{
  "message": "Documento actualizado"
}
```

---

### 8. **delete** - Eliminar documento

Elimina un documento y sus códigos asociados.

```http
GET /api.php?action=delete&cliente=kino&id=1
```

**Parámetros:**
- `cliente` (requerido): Slug del cliente
- `id` (requerido): ID del documento

**Respuesta:**
```json
{
  "message": "Documento eliminado"
}
```

**Nota:** El archivo PDF se elimina físicamente del servidor.

---

### 9. **download_pdfs** - Descargar todos los PDFs en ZIP

Genera un archivo ZIP con todos los PDFs del cliente.

```http
GET /api.php?action=download_pdfs&cliente=kino
```

**Parámetros:**
- `cliente` (requerido): Slug del cliente

**Respuesta:**
Archivo ZIP descargable con nombre: `{cliente}_uploads_{timestamp}.zip`

---

## 🔒 Aislamiento de Datos

Cada acción verifica automáticamente:
- ✅ El cliente existe en `config.json`
- ✅ Solo devuelve/modifica datos del cliente especificado
- ✅ Usa carpeta de uploads aislada: `/uploads/{cliente}/`
- ✅ Filtra tablas BD por columna `cliente`

---

## ⚠️ Códigos de Error

```json
{"error": "Cliente no válido"}           // Cliente no existe en config
{"error": "Acción inválida"}             // action no reconocida
{"error": "No se pudo subir el archivo"} // Error al subir PDF
{"error": "Error de conexión: ..."}      // Error de BD
```

---

## 📊 Formato de Datos

### **Código**
- String (texto)
- Case-insensitive (ABC123 = abc123)
- Sin espacios al inicio/final
- Se ordenan alfabéticamente al guardar

### **Fecha**
- Formato: `YYYY-MM-DD`
- Ejemplo: `2025-01-15`

### **Archivo PDF**
- Tamaño máximo: 10 MB
- Nombre generado: `{timestamp}_{nombre_original}`
- Ejemplo: `1736966400_declaracion.pdf`

---

## 🧪 Ejemplos de Uso

### **JavaScript (Fetch)**

```javascript
// Obtener configuración
const config = await fetch('/api.php?action=get_config&cliente=kino')
  .then(r => r.json());

// Buscar por código
const formData = new FormData();
formData.append('action', 'search_by_code');
formData.append('cliente', 'kino');
formData.append('code', 'ABC123');

const results = await fetch('/api.php', {
  method: 'POST',
  body: formData
}).then(r => r.json());

// Subir documento
const fd = new FormData();
fd.append('action', 'upload');
fd.append('cliente', 'kino');
fd.append('name', 'Mi Documento');
fd.append('date', '2025-01-15');
fd.append('codes', 'ABC123\nXYZ789');
fd.append('file', fileInput.files[0]);

await fetch('/api.php', { method: 'POST', body: fd });
```

### **PHP (cURL)**

```php
// Obtener lista de documentos
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://tudominio.com/api.php?action=list&cliente=kino&per_page=0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
```

---

## 🔄 Flujo Típico de Uso

1. **Cliente carga página admin/pública**
   ```js
   GET /api.php?action=get_config&cliente=kino
   // Carga configuración visual
   ```

2. **Usuario busca código**
   ```js
   POST /api.php
   action=search_by_code&cliente=kino&code=ABC123
   ```

3. **Sistema devuelve documentos**
   ```json
   [
     {
       "id": 1,
       "name": "Declaración 001",
       "date": "2025-01-15",
       "path": "1736966400_doc.pdf",
       "codes": ["ABC123", "XYZ789"]
     }
   ]
   ```

4. **Usuario hace clic en "Ver PDF"**
   ```
   https://tudominio.com/uploads/kino/1736966400_doc.pdf
   ```

---

## 🚀 Optimizaciones

### **Índices de BD**
- `idx_documents_cliente` en `documents(cliente)`
- `idx_codes_cliente` en `codes(cliente)`
- `idx_codes_code` en `codes(code)`

### **Cache (Recomendado)**
- Cache de `get_config` (no cambia frecuentemente)
- Cache de `list` con invalidación al subir/editar/eliminar

### **Compresión**
- Usar `gzip` en respuestas JSON grandes
- Configurar en `.htaccess`

---

## 🔍 Debugging

### **Habilitar errores PHP**
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### **Verificar parámetros recibidos**
```php
error_log(print_r($_REQUEST, true));
error_log(print_r($_FILES, true));
```

### **Logs de consultas SQL**
```php
$stmt->debugDumpParams();
```

---

## 📈 Límites y Recomendaciones

| Concepto | Límite | Recomendación |
|----------|--------|---------------|
| Tamaño archivo | 10 MB | Comprimir PDFs antes de subir |
| Códigos por documento | Ilimitado | Máximo 1000 para rendimiento |
| Documentos por cliente | Ilimitado | Usar paginación si >1000 |
| Clientes en sistema | Ilimitado | Monitorear uso de disco |
| Resultados por página | 50 default | Máximo 100 recomendado |
| Autocompletado | 10 resultados | Suficiente para UX |

---

## 🔄 Versionado

**Versión actual:** 1.0

**Compatibilidad hacia atrás:** Garantizada para v1.x

**Cambios futuros:**
- v1.1: Agregar filtros avanzados
- v1.2: Soporte para múltiples archivos
- v2.0: Autenticación con tokens JWT

---

## 📞 Soporte

Para reportar errores o solicitar funcionalidades:
1. Revisar logs de PHP
2. Revisar consola del navegador
3. Ejecutar `php verify.php`
4. Verificar permisos de carpetas

---

## ✅ Checklist de Integración

- [ ] Verificar que `config.json` tiene el cliente
- [ ] Pasar siempre parámetro `cliente`
- [ ] Manejar respuestas de error
- [ ] Validar datos antes de enviar
- [ ] Usar FormData para uploads
- [ ] Implementar loading states
- [ ] Mostrar mensajes de éxito/error al usuario
- [ ] Limpiar formularios después de submit
- [ ] Recargar lista después de cambios
- [ ] Implementar confirmación antes de eliminar