# API Multi-tenant de Facturación AFIP v2.0

API robusta y escalable para facturación electrónica con AFIP que soporta múltiples clientes y ambos servicios WSFE y WSBFE.

## ✨ Características Principales

- **🏢 Multi-tenant**: Soporte para múltiples clientes con certificados separados
- **🔐 Autenticación por API Key**: Sistema seguro de autenticación
- **📊 Dual Support**: Compatible con WSFE (facturación tradicional) y WSBFE (bonos fiscales)
- **🎯 ARCA 2025 Ready**: Cumplimiento completo con normativa ARCA 2025 (RG N° 5616)
- **🏗️ Arquitectura Moderna**: Patrón MVC con Repository y Service layers
- **📄 PDF Profesional**: Generación automática de PDFs con diseño mejorado
- **📈 Logging y Monitoreo**: Sistema completo de logs y métricas
- **🌐 API RESTful**: Endpoints bien estructurados y documentados
- **⚡ Alto Rendimiento**: Optimizado para múltiples solicitudes concurrentes
- **🔄 Migración Automática**: Sistema de migración de datos legacy a ARCA 2025
- **📊 Dashboard Compliance**: Monitoreo en tiempo real del cumplimiento normativo

## 🚀 Instalación Rápida

### Prerequisitos

- PHP 8.2+
- Composer
- Extensiones PHP: `ext-soap`, `ext-openssl`, `ext-pdo`, `ext-sqlite3`

### Instalación

```bash
# Clonar repositorio
git clone <url-del-repositorio>
cd api-facturacion

# Instalar dependencias
composer install

# Crear directorios necesarios
mkdir -p database logs public/facturas storage/certificates

# Dar permisos
chmod 755 database logs public/facturas storage/certificates

# Inicializar base de datos (se crea automáticamente)
php bin/client-manager.php help
```

## 👥 Gestión de Clientes

### Crear Cliente

```bash
php bin/client-manager.php create \
  --name="Mi Empresa SRL" \
  --cuit="20123456789" \
  --email="admin@miempresa.com" \
  --environment="prod"
```

### Configurar Certificados

```bash
php bin/client-manager.php update-certs \
  --cuit="20123456789" \
  --cert="/path/to/certificado.crt" \
  --key="/path/to/clave.key"
```

### Listar Clientes

```bash
php bin/client-manager.php list
```

### Ver Detalles

```bash
php bin/client-manager.php show --cuit="20123456789"
```

## 🔑 Autenticación

Todas las peticiones requieren autenticación mediante API Key:

### Headers
```http
Authorization: Bearer {API_KEY}
# o
X-API-Key: {API_KEY}
```

### Query Parameter
```http
GET /api/v1/health?api_key={API_KEY}
```

## 📋 Endpoints de la API

### WSFE (Facturación Electrónica Tradicional)

#### Crear Factura
```http
POST /api/v1/wsfe/factura
Content-Type: application/json
Authorization: Bearer {API_KEY}

{
  "PtoVta": 1,
  "TipoComp": 1,
  "facCuit": "20123456789",
  "FechaComp": "15/12/2024",
  "facTotal": 1000.00,
  "incluye_iva": false,
  "facPeriodo_inicio": "01/12/2024",
  "facPeriodo_fin": "31/12/2024",
  "fechaUltimoDia": "15/01/2025",
  "descripcion_servicio": "Servicios de consultoría"
}
```

#### Consultar Comprobante
```http
GET /api/v1/wsfe/comprobante/{ptoVta}/{tipoComp}/{nroComp}
Authorization: Bearer {API_KEY}
```

### WSBFE (Bonos Fiscales Electrónicos)

#### Autorizar Comprobante
```http
POST /api/v1/wsbfe/autorizar
Content-Type: application/json
Authorization: Bearer {API_KEY}

{
  "Tipo_doc": 80,
  "Nro_doc": 20123456789,
  "Zona": 1,
  "Tipo_cbte": 1,
  "Punto_vta": 1,
  "Imp_total": 1000.00,
  "Fecha_cbte": "20241215",
  "Items": [
    {
      "Pro_ds": "Servicios profesionales",
      "Pro_qty": 1,
      "Pro_umed": 7,
      "Pro_precio_uni": 1000.00,
      "Imp_total": 1000.00,
      "Iva_id": 5
    }
  ]
}
```

### Utilidades

#### Consultar CUIT
```http
POST /api/v1/cuit/consultar
Content-Type: application/json
Authorization: Bearer {API_KEY}

{
  "cuit": "20123456789"
}
```

#### Obtener Parámetros
```http
GET /api/v1/parametros/monedas
GET /api/v1/parametros/tipos-comprobante
GET /api/v1/parametros/tipos-iva
GET /api/v1/parametros/condicion-iva-receptor
Authorization: Bearer {API_KEY}
```

#### Health Check
```http
GET /api/v1/health
Authorization: Bearer {API_KEY}
```

## 🎯 ARCA 2025 Compliance (RG N° 5616)

La API incluye soporte completo para el cumplimiento normativo ARCA 2025, incluyendo validación de condición IVA del receptor y nuevas reglas para operaciones en moneda extranjera.

### Características ARCA 2025

- ✅ **Validación Condición IVA Receptor** (obligatorio desde 2025)
- ✅ **Operaciones en Moneda Extranjera** (validación estricta)
- ✅ **Cache de Padrón ARCA** (consultas optimizadas)
- ✅ **Migración Automática** (datos legacy a formato 2025)
- ✅ **Auditoría Completa** (trazabilidad de cambios)
- ✅ **Dashboard de Compliance** (estados en tiempo real)

### Endpoints ARCA 2025

#### Obtener Estado de Compliance
```http
GET /api/arca2025/status
Authorization: Bearer {API_KEY}

Response:
{
  "client_id": "mi-empresa",
  "migration_status": "produccion",
  "compliance_percentage": 95.5,
  "total_invoices": 1000,
  "compliant_invoices": 955,
  "validacion_iva_enabled": true,
  "validacion_padron_enabled": true
}
```

#### Configurar Cliente para ARCA 2025
```http
POST /api/arca2025/configure
Content-Type: application/json
Authorization: Bearer {API_KEY}

{
  "migration_status": "produccion",
  "validacion_iva_enabled": true,
  "validacion_padron_enabled": true,
  "moneda_extranjera_strict": true,
  "compliance_deadline": "2025-12-31"
}
```

#### Validar Compliance de Factura
```http
POST /api/arca2025/validate
Content-Type: application/json
Authorization: Bearer {API_KEY}

{
  "invoice_id": 12345
}

Response:
{
  "invoice_id": 12345,
  "compliance_result": {
    "compliant": true,
    "errors": [],
    "warnings": [],
    "validations": {
      "receptor_iva": {
        "valid": true,
        "codigo": 5,
        "descripcion": "Consumidor Final",
        "source": "padron_arca"
      },
      "moneda_extranjera": {
        "valid": true,
        "warnings": []
      }
    }
  }
}
```

#### Validar CUIT y Condición IVA
```http
POST /api/arca2025/validate-cuit
Content-Type: application/json
Authorization: Bearer {API_KEY}

{
  "cuit": "20123456789",
  "condicion_codigo": 5
}

Response:
{
  "cuit": "20123456789",
  "validation_result": {
    "valid": true,
    "codigo": 5,
    "descripcion": "Consumidor Final",
    "source": "padron_arca"
  }
}
```

#### Migrar Cliente a ARCA 2025
```http
POST /api/arca2025/migrate
Content-Type: application/json
Authorization: Bearer {API_KEY}

{
  "batch_size": 1000,
  "dry_run": false
}

Response:
{
  "message": "Migration completed successfully",
  "migration_id": 123,
  "total_records": 1000,
  "processed_records": 1000
}
```

#### Obtener Reporte de Compliance
```http
GET /api/arca2025/report?period=30&details=true
Authorization: Bearer {API_KEY}

Response:
{
  "client_summary": {
    "client_id": "mi-empresa",
    "compliance_percentage": 95.5,
    "total_invoices": 1000,
    "compliant_invoices": 955
  },
  "period_days": 30,
  "generated_at": "2024-12-27 10:30:00",
  "details": {
    "non_compliant_invoices": [...],
    "recent_audit_log": [...]
  }
}
```

#### Obtener Condiciones IVA Oficiales
```http
GET /api/arca2025/condiciones-iva
Authorization: Bearer {API_KEY}

Response:
{
  "condiciones_iva": [
    {
      "codigo": 1,
      "descripcion": "IVA Responsable Inscripto",
      "descripcion_corta": "Resp. Inscripto",
      "activa": true,
      "obligatoria_2025": true
    },
    {
      "codigo": 5,
      "descripcion": "Consumidor Final",
      "descripcion_corta": "Consumidor Final",
      "activa": true,
      "obligatoria_2025": true
    }
  ],
  "total": 14
}
```

### CLI ARCA 2025

La API incluye una herramienta CLI completa para gestionar ARCA 2025:

#### Verificar Estado de Cliente
```bash
php cli/arca2025.php status mi-empresa
```

#### Configurar Cliente para ARCA 2025
```bash
php cli/arca2025.php configure mi-empresa
# Configuración interactiva:
# ¿Habilitar validación de condición IVA? (s/n): s
# ¿Habilitar validación contra padrón ARCA? (s/n): s
# Estado de migración (preparacion/testing/produccion): produccion
```

#### Migrar Cliente a ARCA 2025
```bash
php cli/arca2025.php migrate mi-empresa
```

#### Generar Reporte de Compliance
```bash
php cli/arca2025.php report                    # Todos los clientes
php cli/arca2025.php report mi-empresa         # Cliente específico
```

#### Probar Validación de CUIT
```bash
php cli/arca2025.php test-cuit 20123456789
```

#### Utilidades de Mantenimiento
```bash
php cli/arca2025.php check-db                  # Verificar estructura BD
php cli/arca2025.php cleanup-cache             # Limpiar cache padrón
php cli/arca2025.php --help                    # Ver todos los comandos
```

### Configuración ARCA 2025

#### Variables de Entorno
```bash
# Estado de migración
ARCA_2025_STATUS=preparacion

# Validación condición IVA
ARCA_VALIDACION_IVA=false
ARCA_IVA_STRICT=false
ARCA_IVA_DEFAULT="IVA Responsable Inscripto"

# Validación padrón ARCA
ARCA_VALIDACION_PADRON=false
ARCA_PADRON_AUTO=false
ARCA_PADRON_CACHE=86400

# Moneda extranjera
ARCA_MONEDA_STRICT=false
ARCA_COTIZACION_DATE=false
ARCA_COTIZACION_MAX_AGE=1

# URLs webservices ARCA 2025
ARCA_WSFE_2025_URL=https://servicios1.afip.gov.ar/wsfev1/service.asmx
ARCA_WSBFE_2025_URL=https://servicios1.afip.gov.ar/wsbfev1/service.asmx
```

#### Archivo de Configuración
El archivo `config/arca_2025.php` contiene toda la configuración específica para ARCA 2025:

```php
return [
    'migration_status' => env('ARCA_2025_STATUS', 'preparacion'),
    'validacion_condicion_iva' => [
        'enabled' => env('ARCA_VALIDACION_IVA', false),
        'strict_mode' => env('ARCA_IVA_STRICT', false),
        'default_condition' => env('ARCA_IVA_DEFAULT', 'IVA Responsable Inscripto'),
    ],
    'validacion_padron' => [
        'enabled' => env('ARCA_VALIDACION_PADRON', false),
        'cache_duration' => env('ARCA_PADRON_CACHE', 86400),
    ],
    'moneda_extranjera' => [
        'strict_validation' => env('ARCA_MONEDA_STRICT', false),
        'require_cotizacion_date' => env('ARCA_COTIZACION_DATE', false),
    ]
];
```

### Base de Datos ARCA 2025

La migración SQL incluye:

#### Nuevas Tablas
- `condiciones_iva` - Códigos oficiales de condiciones IVA
- `client_arca_2025_config` - Configuración por cliente
- `arca_2025_audit` - Auditoría de cambios
- `padron_arca_cache` - Cache de consultas al padrón
- `compliance_notifications` - Notificaciones de compliance
- `arca_2025_migrations` - Seguimiento de migraciones

#### Campos Nuevos en `invoices`
- `receptor_condicion_iva_codigo` - Código de condición IVA (obligatorio 2025)
- `receptor_condicion_iva_descripcion` - Descripción de la condición
- `padron_validacion_realizada` - Si se validó contra padrón ARCA
- `moneda_cotizacion_fecha` - Fecha de cotización para moneda extranjera
- `arca_2025_compliant` - Si cumple con normativa 2025

#### Vista de Dashboard
```sql
-- Vista para monitoreo de compliance
CREATE VIEW arca_2025_compliance_status AS
SELECT 
    c.client_id,
    c.business_name,
    cc.migration_status,
    COUNT(i.id) as total_invoices,
    SUM(i.arca_2025_compliant) as compliant_invoices,
    ROUND((SUM(i.arca_2025_compliant) * 100.0) / COUNT(i.id), 2) as compliance_percentage
FROM clients c
LEFT JOIN client_arca_2025_config cc ON c.client_id = cc.client_id
LEFT JOIN invoices i ON c.client_id = i.client_id
WHERE i.created_at >= '2025-01-01'
GROUP BY c.client_id;
```

### Proceso de Migración

#### 1. Preparación (Q1 2025)
```bash
# Activar modo preparación
php cli/arca2025.php configure mi-empresa
# Estado: preparacion
# Validaciones: deshabilitadas (modo compatible)
```

#### 2. Testing (Q2 2025)
```bash
# Cambiar a modo testing
# Estado: testing
# Validaciones: habilitadas con warnings (no errores)
```

#### 3. Producción (Q3-Q4 2025)
```bash
# Migrar datos legacy
php cli/arca2025.php migrate mi-empresa

# Activar modo producción
# Estado: produccion
# Validaciones: habilitadas con errores obligatorios
```

### Códigos de Condición IVA ARCA 2025

| Código | Descripción | Clase Comp. |
|--------|-------------|-------------|
| 1 | IVA Responsable Inscripto | A, B |
| 4 | IVA Sujeto Exento | A, B |
| 5 | Consumidor Final | B |
| 6 | Responsable Monotributo | A, B |
| 7 | Sujeto no Categorizado | A, B |
| 8 | Proveedor del Exterior | A, B |
| 9 | Cliente del Exterior | A, B |
| 10 | IVA Liberado – Ley Nº 19.640 | A, B |
| 13 | Monotributista Social | A, B |

## 📄 Estructura de Respuestas

### Respuesta Exitosa
```json
{
  "success": true,
  "data": {
    "nro": 12345,
    "CAE": "12345678901234",
    "Vencimiento": "20241231",
    "pdf_filename": "factura_20123456789_20241215123456.pdf",
    "download_link": "https://api.example.com/facturas/factura_20123456789_20241215123456.pdf"
  },
  "timestamp": "2024-12-15T14:30:00-03:00",
  "client": "Mi Empresa SRL"
}
```

### Respuesta de Error
```json
{
  "success": false,
  "error": "Descripción del error",
  "code": 400,
  "timestamp": "2024-12-15T14:30:00-03:00",
  "details": {
    "campo": "valor_invalido"
  }
}
```

## 🐳 Despliegue con Docker

### Inicio Rápido con Script Automático

```bash
# Linux/macOS - Producción
./docker/start.sh production

# Linux/macOS - Desarrollo
./docker/start.sh development

# Windows CMD
docker\start.bat production

# Windows PowerShell (Recomendado)
.\docker\start.ps1 production
.\docker\start.ps1 development
```

### Docker Compose Manual

```bash
# 1. Crear directorios de datos
mkdir -p data/{database,logs,certificates,facturas,uploads}

# 2. Construir y levantar servicios
docker-compose up -d --build

# 3. Verificar estado
docker-compose ps
curl http://localhost:8080/api/v1/health
```

### Configuración docker-compose.yml

El archivo incluye configuración optimizada con:
- Volúmenes persistentes para datos
- Health checks automáticos  
- Variables de entorno configurables
- Límites de recursos
- Configuración de red aislada

### Comandos Docker Útiles

```bash
# Ver logs en tiempo real
docker-compose logs -f afip-api

# Gestión de clientes
docker-compose exec afip-api php bin/client-manager.php list

# Entrar al contenedor
docker-compose exec afip-api bash

# Reiniciar servicios
docker-compose restart

# Detener todo
docker-compose down
```

### Desarrollo con Hot Reload

```bash
# Usar configuración de desarrollo
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Incluye Xdebug, Mailhog y herramientas de desarrollo
```

📋 **Ver documentación completa:**
- [Docker README](docker/README.md) - Guía general
- [Windows Guide](docker/WINDOWS.md) - Guía específica para Windows

## 🔧 Configuración Avanzada

### Variables de Entorno

```bash
# Base de datos
DB_TYPE=sqlite
DB_PATH=database/clients.db

# AFIP
AFIP_ENVIRONMENT=prod  # prod o homo

# Logs
LOG_LEVEL=info

# Archivos
CERTIFICATES_PATH=storage/certificates
FACTURAS_PATH=public/facturas
```

### Certificados por Cliente

```
storage/certificates/
├── client_20123456789/
│   ├── certificado.crt
│   └── private.key
├── client_20987654321/
│   ├── certificado.crt
│   └── private.key
```

## 🐳 Despliegue con Docker

### Despliegue Rápido

```bash
# Clonar repositorio
git clone <url-del-repositorio>
cd api-facturacion

# Construir y levantar servicios
docker-compose up -d

# Verificar servicios
docker-compose ps
docker-compose logs -f api
```

### Configuración Docker

#### Variables de Entorno (.env)
```bash
# PHP Configuration
PHP_MAX_EXECUTION_TIME=300
PHP_MEMORY_LIMIT=512M
PHP_UPLOAD_MAX_FILESIZE=10M

# AFIP Configuration
AFIP_WSDL_WSFE=https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL
AFIP_WSDL_WSBFE=https://servicios1.afip.gov.ar/wsbfev1/service.asmx?WSDL
AFIP_WSDL_WSAA=https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl

# Security
JWT_SECRET=your_jwt_secret_here
API_CORS_ALLOWED_ORIGINS=*

# Database
DATABASE_TYPE=sqlite
DATABASE_PATH=/app/database/clients.db

# ARCA 2025 Configuration
ARCA_2025_STATUS=preparacion
ARCA_VALIDACION_IVA=false
ARCA_VALIDACION_PADRON=false
ARCA_MONEDA_STRICT=false
```

#### Estructura de Volúmenes
```yaml
services:
  api:
    volumes:
      - ./database:/app/database              # Base de datos
      - ./logs:/app/logs                      # Logs del sistema
      - ./storage/certificates:/app/storage/certificates  # Certificados
      - ./public/facturas:/app/public/facturas           # PDFs generados
```

### Scripts de Windows

#### PowerShell (Recomendado)
```powershell
# Iniciar servicios
.\docker\start.ps1

# Ver logs
.\docker\start.ps1 -logs

# Ejecutar tests
.\docker\test-windows.ps1
```

#### CMD
```cmd
# Iniciar servicios  
.\docker\start.bat
```

### Health Check
```bash
# Verificar estado de la API
curl -H "X-API-Key: tu_api_key" http://localhost:8080/api/v1/health

# Verificar compliance ARCA 2025
curl -H "X-API-Key: tu_api_key" http://localhost:8080/api/arca2025/status
```

## 📊 Monitoreo y Logs

### Estructura de Logs

```
logs/
├── system_error.log                    # Errores del sistema
├── api_{cuit}.log                      # Logs por cliente
├── api_error_{cuit}.log               # Errores por cliente
├── baseafipservice_{cuit}_info.log    # Logs de servicios AFIP
├── baseafipservice_{cuit}_error.log   # Errores de servicios AFIP
├── arca_2025_compliance.log           # Logs de compliance ARCA 2025
└── arca_2025_migrations.log           # Logs de migraciones
```

### Base de Datos de Auditoría

```sql
-- Logs de API por cliente
SELECT * FROM api_logs 
WHERE client_id = 1 
ORDER BY created_at DESC;

-- Facturas por cliente
SELECT * FROM invoices 
WHERE client_id = 1 
AND status = 'authorized';

-- Estado compliance ARCA 2025
SELECT * FROM arca_2025_compliance_status;

-- Auditoría ARCA 2025
SELECT * FROM arca_2025_audit 
WHERE client_id = 'mi-empresa' 
ORDER BY created_at DESC;

-- Migraciones ARCA 2025
SELECT * FROM arca_2025_migrations 
WHERE client_id = 'mi-empresa';
```

## 🧪 Ejemplos de Uso

### Ejemplo PHP

```php
<?php

class AfipApiClient 
{
    private string $apiKey;
    private string $baseUrl;
    
    public function __construct(string $apiKey, string $baseUrl)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }
    
    public function crearFactura(array $data): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api/v1/wsfe/factura');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

// Uso
$client = new AfipApiClient('ak_tu_api_key_aqui', 'https://api.example.com');

$factura = $client->crearFactura([
    'PtoVta' => 1,
    'TipoComp' => 1,
    'facCuit' => '20123456789',
    'FechaComp' => date('d/m/Y'),
    'facTotal' => 1000.00,
    'facPeriodo_inicio' => date('01/m/Y'),
    'facPeriodo_fin' => date('t/m/Y'),
    'fechaUltimoDia' => date('d/m/Y', strtotime('+30 days'))
]);

if ($factura['success']) {
    echo "Factura creada: CAE " . $factura['data']['CAE'];
    echo "PDF: " . $factura['data']['download_link'];
}
```

### Ejemplo cURL

```bash
#!/bin/bash
API_KEY="ak_tu_api_key_aqui"
BASE_URL="https://api.example.com"

# Crear factura
curl -X POST "$BASE_URL/api/v1/wsfe/factura" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_KEY" \
  -d '{
    "PtoVta": 1,
    "TipoComp": 1,
    "facCuit": "20123456789",
    "FechaComp": "15/12/2024",
    "facTotal": 1000.00,
    "facPeriodo_inicio": "01/12/2024",
    "facPeriodo_fin": "31/12/2024",
    "fechaUltimoDia": "15/01/2025"
  }' | jq .
```

## 🔒 Seguridad

- ✅ Autenticación obligatoria por API Key
- ✅ Logs de auditoría completos
- ✅ Aislamiento de datos por cliente
- ✅ Validación de certificados AFIP
- ✅ Rate limiting (implementable)
- ✅ HTTPS recomendado en producción

## 📈 Rendimiento

- ✅ Conexiones SOAP reutilizables
- ✅ Cache de credenciales AFIP
- ✅ Logging asíncrono
- ✅ Generación de PDF optimizada
- ✅ Base de datos SQLite para desarrollo
- ✅ Compatible con PostgreSQL/MySQL en producción

## 🐛 Troubleshooting

### Problemas Comunes

#### Error de Certificados
```bash
# Verificar certificados
php bin/client-manager.php show --cuit="20123456789"

# Actualizar certificados
php bin/client-manager.php update-certs --cuit="20123456789" --cert="nuevo.crt" --key="nuevo.key"
```

#### Error de Conexión AFIP
```bash
# Verificar health check
curl -H "Authorization: Bearer {API_KEY}" https://api.example.com/api/v1/health

# Revisar logs
tail -f logs/baseafipservice_*_error.log
```

#### Base de Datos Corrupta
```bash
# Recrear base de datos
rm database/clients.db
php bin/client-manager.php create --name="Test" --cuit="20123456789"
```

### Problemas ARCA 2025

#### Error de Compliance
```bash
# Verificar estado de compliance
php cli/arca2025.php status mi-empresa

# Verificar estructura de BD
php cli/arca2025.php check-db

# Validar factura específica
curl -X POST -H "Authorization: Bearer {API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{"invoice_id":123}' \
  https://api.example.com/api/arca2025/validate
```

#### Error de Migración
```bash
# Ver estado de migración
php cli/arca2025.php migration-status 123

# Consultar logs de migración
tail -f logs/arca_2025_migrations.log

# Reintentar migración
php cli/arca2025.php migrate mi-empresa
```

#### Error de Validación CUIT
```bash
# Probar validación de CUIT
php cli/arca2025.php test-cuit 20123456789

# Limpiar cache de padrón
php cli/arca2025.php cleanup-cache

# Verificar configuración
SELECT * FROM client_arca_2025_config WHERE client_id = 'mi-empresa';
```

#### Error de Configuración ARCA 2025
```bash
# Verificar configuración
php -r "print_r(include 'config/arca_2025.php');"

# Reconfigurar cliente
php cli/arca2025.php configure mi-empresa

# Verificar variables de entorno
printenv | grep ARCA
```

## 🚀 Migración desde v1

### Script de Migración

```bash
# 1. Backup de la configuración actual
cp config/config.php config/config_v1_backup.php

# 2. Crear cliente basado en la configuración v1
php bin/client-manager.php create \
  --name="Cliente Migrado" \
  --cuit="$(grep CUIT_EMISOR config/config.php | cut -d"'" -f4)" \
  --environment="prod"

# 3. Configurar certificados
php bin/client-manager.php update-certs \
  --cuit="..." \
  --cert="$(grep CERT_PATH config/config.php | cut -d"'" -f4)" \
  --key="$(grep KEY_PATH config/config.php | cut -d"'" -f4)"

# 4. Obtener API Key
php bin/client-manager.php show --cuit="..." | grep "API Key"
```

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/amazing-feature`)
3. Commit cambios (`git commit -m 'Add amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abrir Pull Request

## 📋 Documentación Adicional

### Documentos ARCA 2025

- **`ARCA_2025_MIGRATION_PLAN.md`** - Plan detallado de migración a normativa 2025
- **`config/arca_2025.php`** - Configuración completa de compliance
- **`database/migrations/2024_12_27_000000_prepare_arca_2025_compliance.sql`** - Migración de BD
- **`documentacion.md`** - Documentación oficial AFIP/ARCA para WSBFE

### Archivos de Configuración

```
config/
├── database.php          # Configuración de base de datos
├── arca_2025.php         # Configuración ARCA 2025
└── .env.example          # Variables de entorno de ejemplo

cli/
├── client-manager.php    # Gestión de clientes
└── arca2025.php          # Gestión compliance ARCA 2025

api/
├── v1/                   # API endpoints v1 (existente)
└── arca2025.php          # API endpoints ARCA 2025
```

### Roadmap ARCA 2025

| Fase | Periodo | Estado | Descripción |
|------|---------|--------|-------------|
| **Preparación** | Q1 2025 | ✅ **Completado** | Infraestructura y configuración |
| **Testing** | Q2 2025 | 🔄 **En curso** | Validaciones con warnings |
| **Migración** | Q3 2025 | ⏳ **Pendiente** | Migración de datos legacy |
| **Producción** | Q4 2025 | ⏳ **Pendiente** | Compliance obligatorio |

### Estado Actual de la Implementación

✅ **COMPLETADO:**
- Infraestructura completa ARCA 2025
- API REST con 9 endpoints especializados
- CLI de gestión con comandos coloridos
- Base de datos preparatoria (7 tablas nuevas)
- Servicio de compliance con validaciones
- Sistema de migración automática
- Dashboard de compliance en tiempo real
- Auditoría completa de cambios
- Cache inteligente de padrón ARCA
- Documentación técnica completa

⏳ **PENDIENTE (cuando esté disponible la normativa oficial):**
- URLs reales de webservices ARCA 2025
- Consulta real al padrón ARCA (actualmente usa mock)
- Ajustes finales según especificaciones oficiales

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

## 📞 Soporte

- 📧 Email: soporte@tudominio.com
- 📖 Documentación: [docs.tudominio.com](https://docs.tudominio.com)
- 🐛 Issues: [GitHub Issues](https://github.com/tu-usuario/api-facturacion/issues)
- 🎯 ARCA 2025: Para consultas específicas sobre compliance, usar `php cli/arca2025.php --help`

---

**🎉 ¡Felicitaciones! Ahora tienes una API de facturación AFIP robusta, escalable, multi-tenant y preparada para ARCA 2025.** 🎉 