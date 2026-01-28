#!/bin/bash

# ============================================
# Script de Empaquetado - D.Calendar
# ============================================

PLUGIN_NAME="d-calendar"
VERSION="1.0.0"
BUILD_DIR="build"
DIST_DIR="dist"

echo "🚀 Iniciando empaquetado del plugin..."

# 1. Limpiar directorios anteriores
echo "🧹 Limpiando directorios anteriores..."
rm -rf "$BUILD_DIR"
rm -rf "$DIST_DIR"
mkdir -p "$BUILD_DIR"
mkdir -p "$DIST_DIR"

# 2. Copiar archivos del plugin
echo "📁 Copiando archivos del plugin..."
rsync -av --progress \
  --exclude='build' \
  --exclude='dist' \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='.DS_Store' \
  --exclude='node_modules' \
  --exclude='*.log' \
  --exclude='build-plugin.sh' \
  --exclude='.env' \
  --exclude='*.sql' \
  --exclude='*.zip' \
  . "$BUILD_DIR/$PLUGIN_NAME/"

# 3. Instalar dependencias de producción (SIN dev)
echo "📦 Instalando dependencias de Composer..."
cd "$BUILD_DIR/$PLUGIN_NAME"
composer install --no-dev --optimize-autoloader --no-interaction
cd ../..

# 4. Limpiar archivos innecesarios
echo "🧹 Limpiando archivos innecesarios..."
find "$BUILD_DIR" -type f -name ".DS_Store" -delete
find "$BUILD_DIR" -type f -name "Thumbs.db" -delete
find "$BUILD_DIR" -type f -name "*.log" -delete
find "$BUILD_DIR" -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true

# 5. Crear archivo ZIP
echo "🗜️  Creando archivo ZIP..."
cd "$BUILD_DIR"
zip -r "../$DIST_DIR/${PLUGIN_NAME}-${VERSION}.zip" "$PLUGIN_NAME"
cd ..

# 6. Verificar ZIP creado
if [ -f "$DIST_DIR/${PLUGIN_NAME}-${VERSION}.zip" ]; then
    FILE_SIZE=$(du -h "$DIST_DIR/${PLUGIN_NAME}-${VERSION}.zip" | cut -f1)
    echo ""
    echo "✅ ¡Plugin empaquetado exitosamente!"
    echo "📦 Archivo: $DIST_DIR/${PLUGIN_NAME}-${VERSION}.zip"
    echo "💾 Tamaño: $FILE_SIZE"
    echo ""
    echo "📌 Próximos pasos:"
    echo "   1. Ve a WordPress > Plugins > Añadir Nuevo > Subir Plugin"
    echo "   2. Selecciona el archivo: $DIST_DIR/${PLUGIN_NAME}-${VERSION}.zip"
    echo "   3. Haz clic en 'Instalar ahora'"
    echo "   4. Activa el plugin"
    echo ""
else
    echo "❌ Error: No se pudo crear el archivo ZIP"
    exit 1
fi
