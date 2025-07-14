# Script de inicio para API AFIP Multi-tenant en Windows PowerShell
# Uso: .\docker\start.ps1 [production|development]

param(
    [Parameter(Position=0)]
    [ValidateSet("production", "development")]
    [string]$Environment = "production"
)

# Configurar colores
$Host.UI.RawUI.ForegroundColor = "White"

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    
    switch ($Level) {
        "ERROR" { Write-Host "[$timestamp] ❌ $Message" -ForegroundColor Red }
        "SUCCESS" { Write-Host "[$timestamp] ✅ $Message" -ForegroundColor Green }
        "WARNING" { Write-Host "[$timestamp] ⚠️ $Message" -ForegroundColor Yellow }
        default { Write-Host "[$timestamp] $Message" -ForegroundColor Cyan }
    }
}

# Banner
Write-Host @"

   ___    ______ _____ ____     ___    ____  ____
  / _ \  / ____//  _// __ \   /   |  / __ \/  _/
 / /_/ / / /_    / / / /_/ /  / /| | / /_/ // /  
/ __  / / __/  _/ / / ____/  / ___ |/ ____// /   
/_/ /_/ /_/    /___//_/      /_/  |_/_/   /___/   
                                                  
Multi-tenant API de Facturación v2.0

"@ -ForegroundColor Blue

Write-Log "Iniciando en modo: $Environment"

# Verificar Docker
try {
    $dockerVersion = docker --version 2>$null
    if (-not $dockerVersion) {
        Write-Log "Docker no está instalado" "ERROR"
        exit 1
    }
    Write-Log "Docker detectado: $dockerVersion"
} catch {
    Write-Log "Error al verificar Docker: $($_.Exception.Message)" "ERROR"
    exit 1
}

# Verificar Docker Compose
$dockerComposeCmd = "docker compose"
try {
    $composeVersion = & docker compose version 2>$null
    if (-not $composeVersion) {
        $composeVersion = & docker-compose --version 2>$null
        if (-not $composeVersion) {
            Write-Log "Docker Compose no está instalado" "ERROR"
            exit 1
        }
        $dockerComposeCmd = "docker-compose"
    }
    Write-Log "Docker Compose detectado: $composeVersion"
} catch {
    Write-Log "Error al verificar Docker Compose: $($_.Exception.Message)" "ERROR"
    exit 1
}

# Crear directorios de datos necesarios
Write-Log "Creando directorios de datos..."
$dataDirs = @("data", "data\database", "data\logs", "data\certificates", "data\facturas", "data\uploads")
foreach ($dir in $dataDirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Log "Directorio creado: $dir"
    }
}

# Crear archivo de configuración de entorno si no existe
if (-not (Test-Path ".env")) {
    Write-Log "Creando archivo .env..."
    
    $envContent = @"
# Configuración de la API AFIP Multi-tenant
PHP_ENV=$Environment
DB_TYPE=sqlite
DB_PATH=/app/database/clients.db
LOG_LEVEL=info

# URLs de AFIP
AFIP_WSFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsfev1/service.asmx
AFIP_WSFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsfev1/service.asmx
AFIP_WSBFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsbfev1/service.asmx
AFIP_WSBFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsbfev1/service.asmx

# Seguridad
JWT_SECRET=$(([System.Web.Security.Membership]::GeneratePassword(32, 0)) -replace '[^a-zA-Z0-9]', 'x')
API_RATE_LIMIT=100
API_RATE_WINDOW=3600
"@
    
    $envContent | Out-File -FilePath ".env" -Encoding UTF8
    Write-Log "Archivo .env creado" "SUCCESS"
}

# Detener servicios existentes
Write-Log "Deteniendo servicios existentes..."
try {
    & $dockerComposeCmd.Split() down --remove-orphans 2>$null | Out-Null
} catch {
    # Ignorar errores si no hay servicios ejecutándose
}

# Construir imagen
Write-Log "Construyendo imagen Docker..."
try {
    & $dockerComposeCmd.Split() build --no-cache
    if ($LASTEXITCODE -ne 0) {
        Write-Log "Error al construir imagen" "ERROR"
        exit 1
    }
} catch {
    Write-Log "Error al construir imagen: $($_.Exception.Message)" "ERROR"
    exit 1
}

# Iniciar servicios
Write-Log "Iniciando servicios..."
try {
    if ($Environment -eq "development") {
        & $dockerComposeCmd.Split() -f docker-compose.yml -f docker-compose.dev.yml up -d --force-recreate
    } else {
        & $dockerComposeCmd.Split() up -d --force-recreate
    }
    
    if ($LASTEXITCODE -ne 0) {
        Write-Log "Error al iniciar servicios" "ERROR"
        & $dockerComposeCmd.Split() logs --tail=20
        exit 1
    }
} catch {
    Write-Log "Error al iniciar servicios: $($_.Exception.Message)" "ERROR"
    exit 1
}

# Esperar a que los servicios estén listos
Write-Log "Esperando a que los servicios estén listos..."
Start-Sleep -Seconds 15

# Verificar estado de los servicios
Write-Log "Verificando estado de los servicios..."
try {
    $psOutput = & $dockerComposeCmd.Split() ps
    if ($psOutput -match "Up") {
        Write-Log "Servicios iniciados correctamente" "SUCCESS"
    } else {
        Write-Log "Error al iniciar servicios" "ERROR"
        & $dockerComposeCmd.Split() logs --tail=20
        exit 1
    }
} catch {
    Write-Log "Error al verificar servicios: $($_.Exception.Message)" "ERROR"
    exit 1
}

# Verificar health check
Write-Log "Verificando health check..."
$healthOk = $false
for ($i = 1; $i -le 10; $i++) {
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:8080/api/v1/health?api_key=health_check" -UseBasicParsing -TimeoutSec 5 2>$null
        if ($response.StatusCode -eq 200) {
            Write-Log "API funcionando correctamente" "SUCCESS"
            $healthOk = $true
            break
        }
    } catch {
        # Ignorar errores de conexión
    }
    
    Write-Log "Esperando respuesta de la API... (intento $i/10)"
    Start-Sleep -Seconds 3
}

if (-not $healthOk) {
    Write-Log "Health check falló, pero el servicio puede estar iniciando" "WARNING"
}

# Mostrar información de acceso
Write-Host ""
Write-Host "🎉 ¡API iniciada exitosamente!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Información de acceso:" -ForegroundColor Blue
Write-Host "  🌐 URL de la API: " -NoNewline -ForegroundColor Blue
Write-Host "http://localhost:8080" -ForegroundColor Yellow
Write-Host "  📖 Health Check: " -NoNewline -ForegroundColor Blue
Write-Host "http://localhost:8080/api/v1/health" -ForegroundColor Yellow
Write-Host "  📊 Documentación: " -NoNewline -ForegroundColor Blue
Write-Host "http://localhost:8080/api/v1/docs" -ForegroundColor Yellow
Write-Host ""

Write-Host "🔧 Comandos útiles:" -ForegroundColor Blue
Write-Host "  Ver logs:           " -NoNewline -ForegroundColor Blue
Write-Host "$dockerComposeCmd logs -f" -ForegroundColor Yellow
Write-Host "  Detener servicios:  " -NoNewline -ForegroundColor Blue
Write-Host "$dockerComposeCmd down" -ForegroundColor Yellow
Write-Host "  Reiniciar:          " -NoNewline -ForegroundColor Blue
Write-Host "$dockerComposeCmd restart" -ForegroundColor Yellow
Write-Host "  Gestionar clientes: " -NoNewline -ForegroundColor Blue
Write-Host "$dockerComposeCmd exec afip-api php bin/client-manager.php" -ForegroundColor Yellow
Write-Host ""

Write-Host "📁 Directorios de datos:" -ForegroundColor Blue
Write-Host "  Base de datos:  " -NoNewline -ForegroundColor Blue
Write-Host ".\data\database\" -ForegroundColor Yellow
Write-Host "  Logs:          " -NoNewline -ForegroundColor Blue
Write-Host ".\data\logs\" -ForegroundColor Yellow
Write-Host "  Certificados:  " -NoNewline -ForegroundColor Blue
Write-Host ".\data\certificates\" -ForegroundColor Yellow
Write-Host "  Facturas:      " -NoNewline -ForegroundColor Blue
Write-Host ".\data\facturas\" -ForegroundColor Yellow
Write-Host ""

# Mostrar logs en tiempo real si está en modo desarrollo
if ($Environment -eq "development") {
    Write-Log "Mostrando logs en tiempo real (Ctrl+C para salir)..."
    & $dockerComposeCmd.Split() logs -f
}

Write-Host "Presiona cualquier tecla para continuar..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 