# 🎯 Sistema Multicliente - Guía de Instalación

Sistema completo de gestión de documentos con aislamiento total por cliente.

---

## 📁 Estructura de Archivos

```
/
├── config.json              # Configuración de todos los clientes
├── api.php                  # Backend multicliente
├── setup.php                # Script generador automático
├── update_database.sql      # SQL para actualizar BD
│
├── admin/                   # Plantillas y paneles admin
│   ├── index.html          # Plantilla base admin
│   ├── kino/
│   │   └── index.html      # Admin Kino Company
│   └── cliente2/
│       └── index.html      # Admin Cliente 2
│
├── bc/                      # Portales públicos
│   ├── index.html          # Plantilla base pública
│   ├── kino/
│   │   ├── index.html      # Portal Kino Company
│   │   └── Logo-Kino-KB.png
│   └── cliente2/
│       ├── index.html      # Portal Cliente 2
│       └── logo-demo.png
│
├── uploads/                 # Archivos por cliente
│   ├── kino/
│   │   ├── 1234567_doc1.pdf
│   │   └── .htaccess
│   └── cliente2/
│       ├── 1234567_doc2.pdf
│       └── .htaccess
│
└── qr/                      # Sistema de QR codes
    ├── index.php
    └── redirects.json
```

---

## 🚀 Instalación Paso a Paso

### **PASO 1: Actualizar Base de Datos**

Ejecuta el SQL en tu base de datos MySQL:

```bash
mysql -h sql200.infinityfree.com -u if0_39064130 -p if0_39064130_buscador < update_database.sql
```

O accede a phpMyAdmin y ejecuta:

```sql
ALTER TABLE documents ADD COLUMN cliente VARCHAR(50) NOT NULL DEFAULT 'kino' AFTER id;
ALTER TABLE codes ADD COLUMN cliente VARCHAR(50) NOT NULL DEFAULT 'kino' AFTER id;
CREATE INDEX idx_documents_cliente ON documents(cliente);
CREATE INDEX idx_codes_cliente ON codes(cliente);
CREATE INDEX idx_codes_code ON codes(code);
```

---

### **PASO 2: Subir Archivos al Servidor**

Sube estos archivos a tu servidor:

```
✅ config.json
✅ api.php
✅ setup.php
✅ update_database.sql
✅ admin/index.html (plantilla)
✅ bc/index.html (plantilla)
```

---

### **PASO 3: Configurar Clientes**

Edita `config.json` y agrega/modifica clientes:

```json
{
  "clientes": {
    "tu_cliente": {
      "nombre": "TU EMPRESA S.A.S",
      "slug": "tu_cliente",
      "admin": {
        "clave_acceso": "1234",
        "clave_borrado": "5678",
        "titulo": "TU EMPRESA - ADMIN"
      },
      "publico": {
        "logo": "tu-logo.png",
        "titulo": "TU EMPRESA S.A.S",
        "descripcion": "Consulte aquí nuestros documentos...",
        "instrucciones": {
          "paso1": "Busque el código en el producto.",
          "paso2": "Ingrese el código en MAYÚSCULAS.",
          "paso3": "El sistema mostrará los documentos.",
          "paso4": "Haga clic en VER PDF."
        },
        "footer": {
          "descripcion": "TU EMPRESA S.A.S - Importador.",
          "ubicacion": "Tu Ciudad, Tu País.",
          "telefono": "+57 300 1234567",
          "whatsapp": "573001234567",
          "web": "https://tuempresa.com"
        },
        "aviso_legal": "Texto legal personalizado..."
      }
    }
  }
}
```

---

### **PASO 4: Ejecutar Script de Setup**

En el servidor, ejecuta:

```bash
php setup.php
```

Este script automáticamente:
- ✅ Crea carpetas `/admin/cliente/`
- ✅ Crea carpetas `/bc/cliente/`
- ✅ Copia archivos HTML
- ✅ Crea carpetas `/uploads/cliente/`
- ✅ Copia logos

---

### **PASO 5: Subir Logos**

Sube los logos de cada cliente:

```
/bc/kino/Logo-Kino-KB.png
/bc/cliente2/logo-demo.png
```

---

## 🎯 URLs de Acceso

### **Paneles Admin**

Cada cliente tiene su propio panel:

| Cliente | URL | Clave Acceso | Clave Borrado |
|---------|-----|--------------|---------------|
| Kino Company | `https://tudominio.com/admin/kino/` | 565 | 0101 |
| Cliente 2 | `https://tudominio.com/admin/cliente2/` | 1234 | 5678 |

### **Portales Públicos**

URLs públicas para búsqueda:

| Cliente | URL |
|---------|-----|
| Kino Company | `https://tudominio.com/bc/kino/` |
| Cliente 2 | `https://tudominio.com/bc/cliente2/` |

---

## 🔐 Sistema de Seguridad

### **Aislamiento de Datos**

Cada cliente tiene:
- ✅ Sus propios documentos
- ✅ Sus propios códigos
- ✅ Su propia carpeta de uploads
- ✅ No puede ver datos de otros clientes

### **Claves de Acceso**

Cada cliente tiene:
- 🔑 **Clave de acceso**: Para entrar al admin
- 🔑 **Clave de borrado**: Para eliminar documentos

### **Protección de Uploads**

Cada carpeta `/uploads/cliente/` tiene `.htaccess`:

```apache
Options -Indexes
```

---

## 📝 Agregar Nuevo Cliente

### **Opción 1: Automática (Recomendada)**

1. Editar `config.json` y agregar cliente
2. Ejecutar `php setup.php`
3. Subir logo a `/bc/cliente/logo.png`
4. ¡Listo!

### **Opción 2: Manual**

1. Crear carpeta `/admin/nuevo_cliente/`
2. Copiar `/admin/index.html` a `/admin/nuevo_cliente/index.html`
3. Crear carpeta `/bc/nuevo_cliente/`
4. Copiar `/bc/index.html` a `/bc/nuevo_cliente/index.html`
5. Crear carpeta `/uploads/nuevo_cliente/`
6. Agregar cliente a `config.json`

---

## 🎨 Personalización

### **Logo**

Subir imagen en formato PNG o JPG:
- Tamaño recomendado: 300x100px
- Ubicación: `/bc/cliente/logo.png`

### **Textos**

Editar en `config.json`:
- `descripcion`: Texto principal
- `instrucciones`: 4 pasos de uso
- `footer`: Datos de contacto
- `aviso_legal`: Texto legal

### **Colores**

Los estilos están en los archivos HTML. Para cambiar colores:

```css
/* Buscar y reemplazar */
--color-primary: #F87171;      /* Rojo actual */
--color-primary-hover: #DC2626; /* Rojo oscuro */
```

---

## 🔧 Funcionalidades por Cliente

Cada cliente tiene acceso a:

### **Panel Admin**
- ✅ Búsqueda inteligente (algoritmo voraz)
- ✅ Subir documentos PDF
- ✅ Asignar códigos a documentos
- ✅ Editar documentos
- ✅ Eliminar documentos (con clave)
- ✅ Consultar todos sus documentos
- ✅ Filtrar por nombre/PDF
- ✅ Descargar CSV de códigos
- ✅ Descargar ZIP de todos los PDFs
- ✅ Auto-refresh cada 60 segundos
- ✅ Ordenamiento alfabético automático

### **Portal Público**
- ✅ Búsqueda por código individual
- ✅ Autocompletado de códigos
- ✅ Ver PDF en nueva pestaña
- ✅ Diseño responsive
- ✅ Información de contacto
- ✅ Aviso legal

---

## 🐛 Solución de Problemas

### **"Cliente no válido"**

Verificar:
1. Cliente existe en `config.json`
2. URL correcta: `/admin/slug/` o `/bc/slug/`
3. Slug coincide con `config.json`

### **"No se pudo subir el archivo"**

Verificar:
1. Carpeta `/uploads/cliente/` existe
2. Permisos 755: `chmod 755 uploads/cliente`
3. Archivo menor a 10 MB

### **PDFs no se muestran**

Verificar:
1. Ruta correcta: `../../uploads/cliente/archivo.pdf`
2. Archivo existe en servidor
3. Sin errores en consola del navegador

### **Autocompletado no funciona**

Verificar:
1. `action=suggest` en API
2. Índice en columna `code`
3. JavaScript sin errores

---

## 📊 Base de Datos

### **Tabla: documents**

```sql
id INT PRIMARY KEY
cliente VARCHAR(50)  ← NUEVA COLUMNA
name TEXT
date TEXT
path TEXT
```

### **Tabla: codes**

```sql
id INT PRIMARY KEY
cliente VARCHAR(50)  ← NUEVA COLUMNA
document_id INT
code TEXT
```

---

## 🔄 Migrar Datos Existentes

Si ya tienes datos de Kino Company:

```sql
-- Asignar todos los documentos existentes a 'kino'
UPDATE documents SET cliente = 'kino' WHERE cliente = '';
UPDATE codes SET cliente = 'kino' WHERE cliente = '';
```

---

## 📱 QR Codes (Opcional)

Configurar en `/qr/redirects.json`:

```json
{
  "kino": "https://tudominio.com/bc/kino/",
  "cliente2": "https://tudominio.com/bc/cliente2/"
}
```

URL del QR: `https://tudominio.com/qr/?s=kino`

---

## ✅ Checklist de Instalación

- [ ] Actualizar base de datos (SQL)
- [ ] Subir archivos al servidor
- [ ] Configurar `config.json`
- [ ] Ejecutar `php setup.php`
- [ ] Subir logos de clientes
- [ ] Probar admin de cada cliente
- [ ] Probar portal público de cada cliente
- [ ] Subir documento de prueba
- [ ] Verificar búsqueda funciona
- [ ] Configurar QR codes (opcional)

---

## 🎉 ¡Listo!

Tu sistema multicliente está configurado y funcionando.

**Soporte**: Si necesitas ayuda, revisa los logs de PHP y la consola del navegador para identificar errores.

**Actualización**: Para agregar más clientes, solo edita `config.json` y ejecuta `setup.php` nuevamente.