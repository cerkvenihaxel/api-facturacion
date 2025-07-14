# Docker Deployment - API AFIP Multi-tenant

Esta guía explica cómo desplegar la API AFIP Multi-tenant usando Docker y Docker Compose.

## 📋 Requisitos Previos

- Docker 20.10+ instalado
- Docker Compose 2.0+ instalado
- Al menos 1GB de RAM disponible
- Puerto 8080 libre

## 🚀 Inicio Rápido

### Opción 1: Script Automático (Recomendado)

```bash
# Linux/macOS
./docker/start.sh production

# Windows CMD
docker\start.bat production

# Windows PowerShell (Recomendado)
.\docker\start.ps1 production
```

### Opción 2: Manual

```bash
# 1. Crear directorios de datos
mkdir -p data/{database,logs,certificates,facturas,uploads}

# 2. Construir y levantar servicios
docker-compose up -d --build

# 3. Verificar estado
docker-compose ps
```

## 🔧 Configuración

### Variables de Entorno

El archivo `.env` se crea automáticamente, pero puedes personalizarlo:

```env
# Entorno
PHP_ENV=production
LOG_LEVEL=info

# Base de datos
DB_TYPE=sqlite
DB_PATH=/app/database/clients.db

# URLs de AFIP
AFIP_WSFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsfev1/service.asmx
AFIP_WSFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsfev1/service.asmx
AFIP_WSBFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsbfev1/service.asmx
AFIP_WSBFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsbfev1/service.asmx

# Seguridad
JWT_SECRET=tu-clave-secreta-muy-segura
API_RATE_LIMIT=100
API_RATE_WINDOW=3600
```

### Volúmenes Persistentes

Los datos se almacenan en `./data/`:

```
data/
├── database/       # Base de datos SQLite
├── logs/          # Logs de la aplicación
├── certificates/  # Certificados AFIP por cliente
├── facturas/      # PDFs generados
└── uploads/       # Archivos subidos
```

## 📖 Uso

### Acceso a la API

- **URL Base**: `http://localhost:8080`
- **Health Check**: `http://localhost:8080/api/v1/health`
- **Documentación**: `http://localhost:8080/api/v1/docs`

### Gestión de Clientes

```bash
# Crear cliente
docker-compose exec afip-api php bin/client-manager.php create \
  --name "Mi Cliente" \
  --cuit "20123456789" \
  --email "cliente@example.com"

# Listar clientes
docker-compose exec afip-api php bin/client-manager.php list

# Ver cliente específico
docker-compose exec afip-api php bin/client-manager.php show <uuid>
```

### Comandos Útiles

```bash
# Ver logs en tiempo real
docker-compose logs -f afip-api

# Reiniciar servicios
docker-compose restart

# Detener servicios
docker-compose down

# Entrar al contenedor
docker-compose exec afip-api bash

# Backup de base de datos
docker-compose exec afip-api cp /app/database/clients.db /app/data/database/backup.db
```

## 🔍 Monitoreo

### Health Check

El contenedor incluye un health check automático:

```bash
# Verificar estado
docker-compose ps

# Health check manual
curl http://localhost:8080/api/v1/health?api_key=health_check
```

### Logs

```bash
# Logs de la aplicación
docker-compose logs afip-api

# Logs de Apache
docker-compose exec afip-api tail -f /var/log/apache2/error.log

# Logs de la aplicación PHP
docker-compose exec afip-api tail -f /app/logs/app.log
```

## 🛠️ Desarrollo

Para desarrollo con recarga automática:

```bash
# Montar código fuente para desarrollo
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# O usar el script
./docker/start.sh development
```

## 🔒 Seguridad

### Configuración de Producción

1. **Cambiar JWT_SECRET**: Genera una clave segura única
2. **Configurar HTTPS**: Usar proxy reverso (nginx/traefik)
3. **Firewall**: Restringir acceso al puerto 8080
4. **Backups**: Configurar backup automático de `/data`

### Certificados SSL

Para HTTPS, configura un proxy reverso:

```yaml
# nginx.conf
server {
    listen 443 ssl;
    server_name tu-dominio.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 📊 Escalabilidad

### Multi-instancia

```yaml
# docker-compose.scale.yml
services:
  afip-api:
    deploy:
      replicas: 3
  
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    depends_on:
      - afip-api
```

### Base de Datos Externa

Para usar MySQL/PostgreSQL en lugar de SQLite:

```yaml
# docker-compose.prod.yml
services:
  database:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secure_password
      MYSQL_DATABASE: afip_api
    volumes:
      - mysql_data:/var/lib/mysql
  
  afip-api:
    environment:
      DB_TYPE: mysql
      DB_HOST: database
      DB_PORT: 3306
      DB_NAME: afip_api
      DB_USER: afip_user
      DB_PASSWORD: secure_password
```

## 🐛 Troubleshooting

### Problemas Comunes

**Error de permisos**:
```bash
sudo chown -R $(id -u):$(id -g) data/
chmod -R 755 data/
```

**Base de datos corrupta**:
```bash
docker-compose exec afip-api php -r "
require 'vendor/autoload.php';
\AfipApi\Core\Database::initialize();
"
```

**Certificados AFIP**:
```bash
# Verificar certificados por cliente
docker-compose exec afip-api ls -la /app/storage/certificates/
```

### Logs de Debug

Para activar logs detallados:

```bash
# Modificar .env
LOG_LEVEL=debug

# Reiniciar
docker-compose restart afip-api
```

## 📈 Métricas y Monitoreo

### Prometheus (Opcional)

```yaml
# docker-compose.monitoring.yml
services:
  prometheus:
    image: prom/prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
```

### Grafana (Opcional)

```bash
# Instalar Grafana
docker run -d -p 3000:3000 grafana/grafana
```

## 🔄 Actualizaciones

```bash
# 1. Backup
cp -r data/ backup-$(date +%Y%m%d)/

# 2. Actualizar código
git pull origin main

# 3. Reconstruir imagen
docker-compose build --no-cache

# 4. Reiniciar servicios
docker-compose up -d
```

## 💡 Tips

1. **Backups regulares**: Programa backups diarios de `/data`
2. **Monitoreo**: Configura alertas para el health check
3. **Logs**: Rota logs para evitar espacio en disco
4. **Certificados**: Renueva certificados AFIP antes del vencimiento
5. **Actualizaciones**: Mantén la imagen base actualizada

## 📞 Soporte

Para problemas con Docker:

1. Verificar logs: `docker-compose logs`
2. Verificar recursos: `docker stats`
3. Reiniciar servicios: `docker-compose restart`
4. Limpiar sistema: `docker system prune` 