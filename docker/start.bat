@echo off
setlocal enabledelayedexpansion

REM Script de inicio para API AFIP Multi-tenant en Windows
REM Uso: docker\start.bat [production|development]

echo.
echo    ___    ______ _____ ____     ___    ____  ____
echo   / _ \  / ____//  _// __ \   /   ^|  / __ \/  _/
echo  / /_/ / / /_    / / / /_/ /  / /^| ^| / /_/ // /  
echo / __  / / __/  _/ / / ____/  / ___ ^|/ ____// /   
echo /_/ /_/ /_/    /___//_/      /_/  ^|_/_/   /___/   
echo                                                  
echo Multi-tenant API de Facturacion v2.0
echo.

REM Detectar entorno
set ENVIRONMENT=%1
if "%ENVIRONMENT%"=="" set ENVIRONMENT=production
if not "%ENVIRONMENT%"=="production" if not "%ENVIRONMENT%"=="development" (
    echo ❌ Entorno invalido. Usar: production o development
    exit /b 1
)

echo [%date% %time%] Iniciando en modo: %ENVIRONMENT%

REM Verificar Docker
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker no esta instalado
    exit /b 1
)

REM Verificar Docker Compose
docker compose version >nul 2>&1
if errorlevel 1 (
    docker-compose --version >nul 2>&1
    if errorlevel 1 (
        echo ❌ Docker Compose no esta instalado
        exit /b 1
    )
    set DOCKER_COMPOSE_CMD=docker-compose
) else (
    set DOCKER_COMPOSE_CMD=docker compose
)

echo [%date% %time%] Usando comando: %DOCKER_COMPOSE_CMD%

REM Crear directorios de datos necesarios
echo [%date% %time%] Creando directorios de datos...
if not exist "data" mkdir data
if not exist "data\database" mkdir data\database
if not exist "data\logs" mkdir data\logs
if not exist "data\certificates" mkdir data\certificates
if not exist "data\facturas" mkdir data\facturas
if not exist "data\uploads" mkdir data\uploads

REM Crear archivo de configuracion de entorno si no existe
if not exist ".env" (
    echo [%date% %time%] Creando archivo .env...
    (
        echo # Configuracion de la API AFIP Multi-tenant
        echo PHP_ENV=%ENVIRONMENT%
        echo DB_TYPE=sqlite
        echo DB_PATH=/app/database/clients.db
        echo LOG_LEVEL=info
        echo.
        echo # URLs de AFIP
        echo AFIP_WSFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsfev1/service.asmx
        echo AFIP_WSFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsfev1/service.asmx
        echo AFIP_WSBFE_PRODUCTION_URL=https://servicios1.afip.gov.ar/wsbfev1/service.asmx
        echo AFIP_WSBFE_TESTING_URL=https://wswhomo.afip.gov.ar/wsbfev1/service.asmx
        echo.
        echo # Seguridad
        echo JWT_SECRET=change-this-secret-key-in-production
        echo API_RATE_LIMIT=100
        echo API_RATE_WINDOW=3600
    ) > .env
    echo ✅ Archivo .env creado
)

REM Detener servicios existentes
echo [%date% %time%] Deteniendo servicios existentes...
%DOCKER_COMPOSE_CMD% down --remove-orphans >nul 2>&1

REM Construir imagen
echo [%date% %time%] Construyendo imagen Docker...
%DOCKER_COMPOSE_CMD% build --no-cache
if errorlevel 1 (
    echo ❌ Error al construir imagen
    exit /b 1
)

REM Iniciar servicios
echo [%date% %time%] Iniciando servicios...
if "%ENVIRONMENT%"=="development" (
    %DOCKER_COMPOSE_CMD% -f docker-compose.yml -f docker-compose.dev.yml up -d --force-recreate
) else (
    %DOCKER_COMPOSE_CMD% up -d --force-recreate
)

if errorlevel 1 (
    echo ❌ Error al iniciar servicios
    %DOCKER_COMPOSE_CMD% logs --tail=20
    exit /b 1
)

REM Esperar a que los servicios esten listos
echo [%date% %time%] Esperando a que los servicios esten listos...
timeout /t 15 /nobreak >nul

REM Verificar estado de los servicios
echo [%date% %time%] Verificando estado de los servicios...
%DOCKER_COMPOSE_CMD% ps | findstr "Up" >nul
if errorlevel 1 (
    echo ❌ Error al iniciar servicios
    %DOCKER_COMPOSE_CMD% logs --tail=20
    exit /b 1
)

echo ✅ Servicios iniciados correctamente

REM Verificar health check
echo [%date% %time%] Verificando health check...
set "health_ok=false"
for /l %%i in (1,1,10) do (
    curl -s -f http://localhost:8080/api/v1/health?api_key=health_check >nul 2>&1
    if not errorlevel 1 (
        echo ✅ API funcionando correctamente
        set "health_ok=true"
        goto :health_done
    )
    echo Esperando respuesta de la API... ^(intento %%i/10^)
    timeout /t 3 /nobreak >nul
)

:health_done
if "%health_ok%"=="false" (
    echo ⚠️ Health check fallo, pero el servicio puede estar iniciando
)

REM Mostrar informacion de acceso
echo.
echo 🎉 ¡API iniciada exitosamente!
echo.
echo 📋 Informacion de acceso:
echo   🌐 URL de la API: http://localhost:8080
echo   📖 Health Check: http://localhost:8080/api/v1/health
echo   📊 Documentacion: http://localhost:8080/api/v1/docs
echo.
echo 🔧 Comandos utiles:
echo   Ver logs:           %DOCKER_COMPOSE_CMD% logs -f
echo   Detener servicios:  %DOCKER_COMPOSE_CMD% down
echo   Reiniciar:          %DOCKER_COMPOSE_CMD% restart
echo   Gestionar clientes: %DOCKER_COMPOSE_CMD% exec afip-api php bin/client-manager.php
echo.
echo 📁 Directorios de datos:
echo   Base de datos:  .\data\database\
echo   Logs:          .\data\logs\
echo   Certificados:  .\data\certificates\
echo   Facturas:      .\data\facturas\
echo.

REM Mostrar logs en tiempo real si esta en modo desarrollo
if "%ENVIRONMENT%"=="development" (
    echo [%date% %time%] Mostrando logs en tiempo real ^(Ctrl+C para salir^)...
    %DOCKER_COMPOSE_CMD% logs -f
)

pause 