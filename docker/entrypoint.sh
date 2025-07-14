#!/bin/bash
set -e

echo "🚀 Iniciando API Multi-tenant de Facturación AFIP v2.0"

# Función para logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Verificar si estamos ejecutando como root (necesario para inicialización)
if [ "$(id -u)" = "0" ]; then
    log "Ejecutando inicialización como root..."
    
    # Crear directorios si no existen
    log "Creando directorios necesarios..."
    mkdir -p /app/database /app/logs /app/public/facturas /app/storage/certificates /app/storage/uploads /app/var/cache /app/var/tmp
    
    # Establecer permisos correctos
    log "Configurando permisos..."
    chown -R afipapi:afipapi /app/database /app/logs /app/public/facturas /app/storage /app/var
    chmod -R 755 /app/database /app/logs /app/public/facturas /app/storage /app/var
    
    # Configurar permisos para Apache
    chown -R www-data:www-data /app/public 2>/dev/null || true
    chmod -R 755 /app/public 2>/dev/null || true
    
    # Asegurar que el archivo de base de datos sea escribible
    if [ ! -f /app/database/clients.db ]; then
        log "Inicializando base de datos..."
        touch /app/database/clients.db
        chown afipapi:afipapi /app/database/clients.db
        chmod 664 /app/database/clients.db
    fi
    
    # Verificar configuración de Apache
    log "Verificando configuración de Apache..."
    apache2ctl configtest
fi

# Ejecutamos las verificaciones necesarias
log "Ejecutando verificaciones..."

# Verificar e inicializar la base de datos si es necesario
log "Verificando base de datos..."
if [ ! -s "/app/database/clients.db" ]; then
    log "Base de datos vacía, inicializando..."
    cd /app
    php -r "
        require_once 'vendor/autoload.php';
        try {
            \AfipApi\Core\Database::initialize();
            echo 'Base de datos inicializada correctamente\n';
        } catch (Exception \$e) {
            echo 'Error al inicializar base de datos: ' . \$e->getMessage() . '\n';
            exit(1);
        }
    "
fi

# Verificar que las dependencias estén instaladas
log "Verificando dependencias de Composer..."
if [ ! -d "/app/vendor" ]; then
    log "Instalando dependencias..."
    cd /app
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Verificar configuración de PHP
log "Verificando configuración de PHP..."
php -v
php -m | grep -E "(soap|pdo|sqlite|gd)" || {
    log "ERROR: Faltan extensiones PHP requeridas"
    exit 1
}

# Limpiar cache si existe
log "Limpiando cache..."
rm -rf /app/var/cache/* /app/var/tmp/*

# Verificar permisos finales
log "Verificando permisos finales..."
if [ ! -w "/app/logs" ]; then
    log "WARNING: Directorio logs no es escribible"
fi

if [ ! -w "/app/public/facturas" ]; then
    log "WARNING: Directorio facturas no es escribible"
fi

if [ ! -w "/app/database" ]; then
    log "WARNING: Directorio database no es escribible"
fi

# Mostrar información del sistema
log "Información del sistema:"
log "- PHP Version: $(php -v | head -n 1)"
log "- Composer Version: $(composer --version)"
log "- Usuario actual: $(whoami) ($(id))"
log "- Directorio de trabajo: $(pwd)"
log "- Variables de entorno relevantes:"
log "  * PHP_ENV: ${PHP_ENV:-'no definido'}"
log "  * DB_PATH: ${DB_PATH:-'no definido'}"
log "  * LOG_LEVEL: ${LOG_LEVEL:-'no definido'}"

# Mostrar estadísticas de base de datos
if [ -f "/app/database/clients.db" ]; then
    CLIENT_COUNT=$(sqlite3 /app/database/clients.db "SELECT COUNT(*) FROM clients;" 2>/dev/null || echo "0")
    log "- Clientes registrados: $CLIENT_COUNT"
fi

log "✅ Inicialización completada exitosamente"

# Si el primer argumento es apache2-foreground, ejecutarlo
if [ "$1" = "apache2-foreground" ]; then
    log "🌐 Iniciando servidor Apache..."
    exec apache2-foreground
else
    # Para otros comandos, ejecutar directamente
    log "🔧 Ejecutando comando: $*"
    exec "$@"
fi 