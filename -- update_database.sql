-- update_database.sql
-- Agregar columna 'cliente' a las tablas existentes

-- 1. Agregar columna a la tabla documents
ALTER TABLE documents 
ADD COLUMN cliente VARCHAR(50) NOT NULL DEFAULT 'kino' AFTER id;

-- 2. Agregar columna a la tabla codes
ALTER TABLE codes 
ADD COLUMN cliente VARCHAR(50) NOT NULL DEFAULT 'kino' AFTER id;

-- 3. Crear índices para mejorar rendimiento
CREATE INDEX idx_documents_cliente ON documents(cliente);
CREATE INDEX idx_codes_cliente ON codes(cliente);
CREATE INDEX idx_codes_code ON codes(code);

-- 4. Actualizar registros existentes (si los hay)
-- Esto asigna 'kino' como cliente por defecto a todos los registros existentes
UPDATE documents SET cliente = 'kino' WHERE cliente = '';
UPDATE codes SET cliente = 'kino' WHERE cliente = '';

-- 5. Verificar estructura
-- SHOW COLUMNS FROM documents;
-- SHOW COLUMNS FROM codes;