# API AFIP Multi-tenant - Guía para Windows

Esta guía específica te ayudará a ejecutar la API AFIP Multi-tenant en Windows usando Docker.

## 📋 Requisitos Previos para Windows

### 1. Docker Desktop para Windows
Descarga e instala Docker Desktop desde: https://www.docker.com/products/docker-desktop/

**Configuración recomendada:**
- WSL 2 habilitado (recomendado)
- Al menos 4GB de RAM asignada a Docker
- Virtualización habilitada en BIOS

### 2. PowerShell 5.1+ o PowerShell Core 7+
Verifica tu versión:
```powershell
$PSVersionTable.PSVersion
```

### 3. Git para Windows (Opcional)
Para clonar el repositorio: https://git-scm.com/download/win

## 🚀 Inicio Rápido en Windows

### Opción 1: PowerShell (Recomendado)

Abre PowerShell como Administrador y ejecuta:

```powershell
# Navegar al directorio del proyecto
cd D:\api-facturacion

# Ejecutar script de inicio
.\docker\start.ps1 production

# Para desarrollo
.\docker\start.ps1 development
```

### Opción 2: Command Prompt (CMD)

Abre CMD como Administrador:

```cmd
cd D:\api-facturacion
docker\start.bat production
```

### Opción 3: Manual

```cmd
# Crear directorios
mkdir data\database data\logs data\certificates data\facturas data\uploads

# Construir y ejecutar
docker-compose up -d --build

# Verificar estado
docker-compose ps
```

## 🔧 Configuración Específica para Windows

### Configurar Política de Ejecución de PowerShell

Si encuentras errores de política de ejecución:

```powershell
# Verificar política actual
Get-ExecutionPolicy

# Permitir scripts locales (como Administrador)
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

# O temporalmente para esta sesión
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
```

### Variables de Entorno Windows

El script creará automáticamente el archivo `.env`, pero puedes editarlo:

```env
# Rutas Windows en el contenedor (no cambiar)
DB_PATH=/app/database/clients.db
CERTIFICATES_PATH=/app/storage/certificates
FACTURAS_PATH=/app/public/facturas

# Configuración específica Windows
COMPOSE_CONVERT_WINDOWS_PATHS=1
```

### Solución de Problemas Comunes en Windows

#### 1. Error "docker: command not found"

```powershell
# Verificar instalación
docker --version
docker-compose --version

# Si no funciona, reiniciar Docker Desktop
```

#### 2. Problemas de Permisos

```powershell
# Ejecutar PowerShell como Administrador
# Verificar que Docker Desktop esté corriendo
```

#### 3. Error de Volume Mounts

Si tienes problemas con los volúmenes:

```yaml
# En docker-compose.yml, cambiar de:
- ./data/database:/app/database

# A rutas absolutas:
- D:/api-facturacion/data/database:/app/database
```

#### 4. Puertos Ocupados

```powershell
# Verificar qué está usando el puerto 8080
netstat -ano | findstr :8080

# Terminar proceso si es necesario
taskkill /PID <PID> /F
```

## 📁 Estructura de Directorios en Windows

```
D:\api-facturacion\
├── data\
│   ├── database\          # Base de datos SQLite
│   ├── logs\             # Logs de la aplicación
│   ├── certificates\     # Certificados AFIP
│   ├── facturas\         # PDFs generados
│   └── uploads\          # Archivos subidos
├── docker\
│   ├── start.bat         # Script CMD
│   ├── start.ps1         # Script PowerShell
│   └── README.md
└── docker-compose.yml
```

## 🔧 Comandos Útiles para Windows

### PowerShell

```powershell
# Ver logs en tiempo real
docker-compose logs -f afip-api

# Entrar al contenedor
docker-compose exec afip-api bash

# Gestionar clientes
docker-compose exec afip-api php bin/client-manager.php list

# Crear cliente
docker-compose exec afip-api php bin/client-manager.php create `
  --name "Mi Cliente" `
  --cuit "20123456789" `
  --email "cliente@example.com"

# Detener servicios
docker-compose down

# Limpiar todo (cuidado: borra datos)
docker-compose down -v
docker system prune -a
```

### CMD

```cmd
REM Ver logs
docker-compose logs -f afip-api

REM Entrar al contenedor
docker-compose exec afip-api bash

REM Detener servicios
docker-compose down
```

## 🌐 Acceso desde Windows

- **URL Base**: http://localhost:8080
- **Health Check**: http://localhost:8080/api/v1/health
- **Documentación**: http://localhost:8080/api/v1/docs

### Probar desde PowerShell

```powershell
# Health check
Invoke-RestMethod -Uri "http://localhost:8080/api/v1/health?api_key=health_check"

# Con autenticación
$headers = @{
    'Authorization' = 'Bearer tu_api_key_aqui'
    'Content-Type' = 'application/json'
}

Invoke-RestMethod -Uri "http://localhost:8080/api/v1/wsfe/factura" -Method POST -Headers $headers -Body $json
```

## 🔒 Seguridad en Windows

### Windows Defender

Asegúrate de que Windows Defender no bloquee Docker:

1. Abrir Windows Security
2. Ir a "Virus & threat protection"
3. Agregar exclusiones para:
   - Directorio del proyecto
   - Docker Desktop
   - WSL 2 (si lo usas)

### Firewall

El Windows Firewall puede bloquear conexiones:

```powershell
# Permitir aplicación a través del firewall (como Administrador)
New-NetFirewallRule -DisplayName "Docker Desktop" -Direction Inbound -Action Allow -Program "C:\Program Files\Docker\Docker\Docker Desktop.exe"
```

## 🛠️ Desarrollo en Windows

### Visual Studio Code + Docker

1. Instalar extensiones:
   - Docker
   - Remote-Containers
   - PHP Intelephense

2. Configurar `settings.json`:
```json
{
    "terminal.integrated.shell.windows": "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe",
    "docker.host": "npipe:////./pipe/docker_engine"
}
```

### WSL 2 (Recomendado para Desarrollo)

```bash
# Desde WSL 2
cd /mnt/d/api-facturacion
./docker/start.sh development
```

## 📊 Monitoreo en Windows

### Task Manager

Monitorear recursos de Docker:
- Abrir Task Manager (Ctrl+Shift+Esc)
- Ir a "Performance" > "Memory" y "CPU"
- Buscar procesos de Docker

### Docker Desktop Dashboard

- Abrir Docker Desktop
- Ir a "Containers" para ver estado
- Usar "Images" para gestionar imágenes

## 🔄 Actualizaciones

```powershell
# Actualizar código
git pull origin main

# Reconstruir imagen
docker-compose build --no-cache

# Reiniciar servicios
docker-compose up -d
```

## 💡 Tips para Windows

1. **Usar PowerShell ISE** para editar scripts
2. **Docker Desktop** debe estar corriendo antes de ejecutar comandos
3. **WSL 2** mejora significativamente el rendimiento
4. **Exclusiones de antivirus** para directorios de Docker
5. **Reiniciar Docker Desktop** si hay problemas de conexión

## 📞 Soporte Windows

Si tienes problemas específicos de Windows:

1. Verificar logs de Docker Desktop
2. Revisar Event Viewer de Windows
3. Comprobar versión de WSL: `wsl --status`
4. Reiniciar servicios de Docker

### Logs de Docker Desktop

```
C:\Users\%USERNAME%\AppData\Local\Docker\log.txt
```

### Reinstalar Docker Desktop

Si nada funciona:

1. Desinstalar Docker Desktop
2. Limpiar registros y archivos
3. Reiniciar Windows
4. Reinstalar Docker Desktop
5. Configurar WSL 2 