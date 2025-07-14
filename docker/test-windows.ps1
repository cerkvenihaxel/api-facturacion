# Script de pruebas para Windows - API AFIP Multi-tenant
# Uso: .\docker\test-windows.ps1

param(
    [string]$BaseUrl = "http://localhost:8080"
)

Write-Host "🧪 Ejecutando pruebas de la API AFIP Multi-tenant" -ForegroundColor Blue
Write-Host "URL Base: $BaseUrl" -ForegroundColor Yellow
Write-Host ""

# Función para hacer requests HTTP
function Test-ApiEndpoint {
    param(
        [string]$Url,
        [string]$Method = "GET",
        [hashtable]$Headers = @{},
        [string]$Body = $null,
        [string]$Description
    )
    
    Write-Host "📋 $Description" -ForegroundColor Cyan
    Write-Host "   → $Method $Url" -ForegroundColor Gray
    
    try {
        $params = @{
            Uri = $Url
            Method = $Method
            UseBasicParsing = $true
            TimeoutSec = 10
        }
        
        if ($Headers.Count -gt 0) {
            $params.Headers = $Headers
        }
        
        if ($Body) {
            $params.Body = $Body
            $params.ContentType = "application/json"
        }
        
        $response = Invoke-WebRequest @params
        
        if ($response.StatusCode -eq 200) {
            Write-Host "   ✅ OK ($($response.StatusCode))" -ForegroundColor Green
            
            # Mostrar respuesta si es JSON pequeño
            if ($response.Content.Length -lt 500) {
                try {
                    $json = $response.Content | ConvertFrom-Json
                    Write-Host "   📄 Respuesta: $($json | ConvertTo-Json -Compress)" -ForegroundColor Gray
                } catch {
                    Write-Host "   📄 Respuesta: $($response.Content)" -ForegroundColor Gray
                }
            }
        } else {
            Write-Host "   ⚠️ Status: $($response.StatusCode)" -ForegroundColor Yellow
        }
        
        return $true
    } catch {
        Write-Host "   ❌ Error: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    
    Write-Host ""
}

# 1. Verificar que Docker esté corriendo
Write-Host "🐳 Verificando Docker..." -ForegroundColor Blue
try {
    $containers = docker ps --filter "name=afip-api" --format "table {{.Names}}\t{{.Status}}"
    if ($containers -match "afip-api") {
        Write-Host "   ✅ Contenedor API ejecutándose" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Contenedor API no encontrado" -ForegroundColor Red
        Write-Host "   💡 Ejecuta: .\docker\start.ps1 production" -ForegroundColor Yellow
        exit 1
    }
} catch {
    Write-Host "   ❌ Docker no está disponible" -ForegroundColor Red
    exit 1
}

Write-Host ""

# 2. Health Check
$healthOk = Test-ApiEndpoint -Url "$BaseUrl/api/v1/health?api_key=health_check" -Description "Health Check"

if (-not $healthOk) {
    Write-Host "❌ La API no está respondiendo. Verifica que esté ejecutándose." -ForegroundColor Red
    exit 1
}

# 3. Verificar endpoints principales
$endpoints = @(
    @{
        Url = "$BaseUrl/"
        Description = "Página principal"
    },
    @{
        Url = "$BaseUrl/api/v1/health"
        Description = "Health check sin API key"
    }
)

foreach ($endpoint in $endpoints) {
    Test-ApiEndpoint -Url $endpoint.Url -Description $endpoint.Description | Out-Null
}

# 4. Verificar directorios de datos
Write-Host "📁 Verificando estructura de directorios..." -ForegroundColor Blue

$directories = @(
    "data",
    "data\database",
    "data\logs", 
    "data\certificates",
    "data\facturas",
    "data\uploads"
)

foreach ($dir in $directories) {
    if (Test-Path $dir) {
        Write-Host "   ✅ $dir" -ForegroundColor Green
    } else {
        Write-Host "   ❌ $dir (faltante)" -ForegroundColor Red
    }
}

Write-Host ""

# 5. Verificar archivos de base de datos
Write-Host "🗄️ Verificando base de datos..." -ForegroundColor Blue

if (Test-Path "data\database\clients.db") {
    $dbSize = (Get-Item "data\database\clients.db").Length
    Write-Host "   ✅ Base de datos existe ($dbSize bytes)" -ForegroundColor Green
    
    # Intentar verificar contenido usando Docker
    try {
        $clientCount = docker-compose exec -T afip-api sqlite3 /app/database/clients.db "SELECT COUNT(*) FROM clients;" 2>$null
        if ($clientCount -match '\d+') {
            Write-Host "   📊 Clientes registrados: $($clientCount.Trim())" -ForegroundColor Cyan
        }
    } catch {
        Write-Host "   ⚠️ No se pudo consultar la base de datos" -ForegroundColor Yellow
    }
} else {
    Write-Host "   ❌ Base de datos no encontrada" -ForegroundColor Red
}

Write-Host ""

# 6. Verificar logs
Write-Host "📝 Verificando logs..." -ForegroundColor Blue

$logFiles = Get-ChildItem -Path "data\logs" -Filter "*.log" -ErrorAction SilentlyContinue

if ($logFiles.Count -gt 0) {
    Write-Host "   ✅ Archivos de log encontrados:" -ForegroundColor Green
    foreach ($logFile in $logFiles) {
        $size = [math]::Round($logFile.Length / 1KB, 2)
        Write-Host "     • $($logFile.Name) (${size}KB)" -ForegroundColor Gray
    }
} else {
    Write-Host "   ⚠️ No se encontraron archivos de log" -ForegroundColor Yellow
}

Write-Host ""

# 7. Verificar configuración
Write-Host "⚙️ Verificando configuración..." -ForegroundColor Blue

if (Test-Path ".env") {
    Write-Host "   ✅ Archivo .env existe" -ForegroundColor Green
    
    $envContent = Get-Content ".env" | Where-Object { $_ -notmatch '^#' -and $_ -ne '' }
    Write-Host "   📋 Variables configuradas: $($envContent.Count)" -ForegroundColor Cyan
} else {
    Write-Host "   ❌ Archivo .env no encontrado" -ForegroundColor Red
}

if (Test-Path "docker-compose.yml") {
    Write-Host "   ✅ docker-compose.yml existe" -ForegroundColor Green
} else {
    Write-Host "   ❌ docker-compose.yml no encontrado" -ForegroundColor Red
}

Write-Host ""

# 8. Resumen de pruebas
Write-Host "📊 Resumen de Pruebas" -ForegroundColor Blue
Write-Host "════════════════════" -ForegroundColor Blue

$status = if ($healthOk) { "✅ FUNCIONANDO" } else { "❌ CON PROBLEMAS" }
Write-Host "Estado de la API: $status" -ForegroundColor $(if ($healthOk) { "Green" } else { "Red" })

Write-Host ""
Write-Host "🔗 Enlaces útiles:" -ForegroundColor Blue
Write-Host "   🌐 API: $BaseUrl" -ForegroundColor Yellow
Write-Host "   📖 Health: $BaseUrl/api/v1/health" -ForegroundColor Yellow
Write-Host "   📊 Docs: $BaseUrl/api/v1/docs" -ForegroundColor Yellow

Write-Host ""
Write-Host "🔧 Comandos útiles:" -ForegroundColor Blue
Write-Host "   Ver logs:     docker-compose logs -f afip-api" -ForegroundColor Yellow
Write-Host "   Reiniciar:    docker-compose restart" -ForegroundColor Yellow
Write-Host "   Detener:      docker-compose down" -ForegroundColor Yellow

if (-not $healthOk) {
    Write-Host ""
    Write-Host "💡 Si hay problemas:" -ForegroundColor Yellow
    Write-Host "   1. Verificar que Docker Desktop esté corriendo" -ForegroundColor Gray
    Write-Host "   2. Ejecutar: .\docker\start.ps1 production" -ForegroundColor Gray
    Write-Host "   3. Revisar logs: docker-compose logs afip-api" -ForegroundColor Gray
}

Write-Host ""
Write-Host "Presiona cualquier tecla para continuar..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 