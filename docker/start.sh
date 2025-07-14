#!/bin/bash

# Script de inicio rápido para API AFIP Multi-tenant
# Uso: ./docker/start.sh [production|development]

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para logging
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Banner
echo -e "${BLUE}"
cat << "EOF"
   ___    ______ _____ ____     ___    ____  ____
  / _ \  / ____//  _// __ \   /   |  / __ \/  _/
 / /_/ / / /_    / / / /_/ /  / /| | / /_/ // /  
/ __  / / __/  _/ / / ____/  / ___ |/ ____// /   
/_/ /_/ /_/    /___//_/      /_/  |_/_/   /___/   
                                                  
Multi-tenant API de Facturación v2.0
EOF
echo -e "${NC}"

# Detectar entorno
ENVIRONMENT=${1:-production}
if [ "$ENVIRONMENT" != "production" ] && [ "$ENVIRONMENT" != "development" ]; then
    error "Entorno inválido. Usar: production o development"
    exit 1
fi

log "Iniciando en modo: $ENVIRONMENT"

# Verificar Docker y Docker Compose
if ! command -v docker &> /dev/null; then
    error "Docker no está instalado"
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    error "Docker Compose no está instalado"
    exit 1
fi

# Usar docker compose o docker-compose
DOCKER_COMPOSE_CMD="docker compose"
if ! docker compose version &> /dev/null; then
    DOCKER_COMPOSE_CMD="docker-compose"
fi

# Crear directorios de datos necesarios
log "Creando directorios de datos..."
mkdir -p data/{database,logs,certificates,facturas,uploads}

# Verificar permisos
if [ ! -w "data" ]; then
    warning "Ajustando permisos de directorio data..."
    sudo chown -R $(id -u):$(id -g) data/
    chmod -R 755 data/
fi

# Verificar configuración
log "Verificando configuración..."

# Crear archivo de configuración de entorno si no existe
if [ ! -f ".env" ]; then
    log "Creando archivo .env..."
    cat > .env << EOF
# Configuración de la API AFIP Multi-tenant
PHP_ENV=$ENVIRONMENT
DB_TYPE=sqlite
DB_PATH=/app/database/clients.db
LOG_LEVEL=info

# URLs de AFIP
AFIP_WSFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsfev1/service.asmx
AFIP_WSFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsfev1/service.asmx
AFIP_WSBFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsbfev1/service.asmx
AFIP_WSBFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsbfev1/service.asmx

# Seguridad
JWT_SECRET=$(openssl rand -base64 32 | tr -d '\n')
API_RATE_LIMIT=100
API_RATE_WINDOW=3600
EOF
    success "Archivo .env creado"
fi

# Detener servicios existentes
log "Deteniendo servicios existentes..."
$DOCKER_COMPOSE_CMD down --remove-orphans 2>/dev/null || true

# Construir imagen
log "Construyendo imagen Docker..."
$DOCKER_COMPOSE_CMD build --no-cache

# Iniciar servicios
log "Iniciando servicios..."
if [ "$ENVIRONMENT" = "development" ]; then
    $DOCKER_COMPOSE_CMD up -d --force-recreate
else
    $DOCKER_COMPOSE_CMD up -d --force-recreate
fi

# Esperar a que los servicios estén listos
log "Esperando a que los servicios estén listos..."
sleep 10

# Verificar estado de los servicios
log "Verificando estado de los servicios..."
if $DOCKER_COMPOSE_CMD ps | grep -q "Up"; then
    success "Servicios iniciados correctamente"
else
    error "Error al iniciar servicios"
    $DOCKER_COMPOSE_CMD logs --tail=20
    exit 1
fi

# Verificar health check
log "Verificando health check..."
for i in {1..10}; do
    if curl -s -f http://localhost:8080/api/v1/health?api_key=health_check >/dev/null 2>&1; then
        success "API funcionando correctamente"
        break
    else
        if [ $i -eq 10 ]; then
            warning "Health check falló, pero el servicio puede estar iniciando"
        else
            log "Esperando respuesta de la API... (intento $i/10)"
            sleep 3
        fi
    fi
done

# Mostrar información de acceso
echo ""
echo -e "${GREEN}🎉 ¡API iniciada exitosamente!${NC}"
echo ""
echo -e "${BLUE}📋 Información de acceso:${NC}"
echo -e "  🌐 URL de la API: ${YELLOW}http://localhost:8080${NC}"
echo -e "  📖 Health Check: ${YELLOW}http://localhost:8080/api/v1/health${NC}"
echo -e "  📊 Documentación: ${YELLOW}http://localhost:8080/api/v1/docs${NC}"
echo ""
echo -e "${BLUE}🔧 Comandos útiles:${NC}"
echo -e "  Ver logs:           ${YELLOW}$DOCKER_COMPOSE_CMD logs -f${NC}"
echo -e "  Detener servicios:  ${YELLOW}$DOCKER_COMPOSE_CMD down${NC}"
echo -e "  Reiniciar:          ${YELLOW}$DOCKER_COMPOSE_CMD restart${NC}"
echo -e "  Gestionar clientes: ${YELLOW}$DOCKER_COMPOSE_CMD exec afip-api php bin/client-manager.php${NC}"
echo ""
echo -e "${BLUE}📁 Directorios de datos:${NC}"
echo -e "  Base de datos:  ${YELLOW}./data/database/${NC}"
echo -e "  Logs:          ${YELLOW}./data/logs/${NC}"
echo -e "  Certificados:  ${YELLOW}./data/certificates/${NC}"
echo -e "  Facturas:      ${YELLOW}./data/facturas/${NC}"
echo ""

# Mostrar logs en tiempo real si está en modo desarrollo
if [ "$ENVIRONMENT" = "development" ]; then
    log "Mostrando logs en tiempo real (Ctrl+C para salir)..."
    $DOCKER_COMPOSE_CMD logs -f
fi 