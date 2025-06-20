# API de Facturación AFIP

API para facturación electrónica con AFIP (Administración Federal de Ingresos Públicos) de Argentina.

## Requisitos

- **PHP 8.2 o superior**
- Composer
- Extensiones PHP requeridas:
  - `ext-soap`
  - `ext-openssl`
  - `ext-gd` (para FPDF)
  - `ext-zlib` (para FPDF)

## Instalación

### Opción 1: Instalación Local

1. Clonar el repositorio:
```bash
git clone <url-del-repositorio>
cd api-facturacion
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar certificados:
   - Colocar el certificado `.crt` en `certs/certificado.crt`
   - Colocar la clave privada `.key` en `certs/rehabilitarte.key`

4. Crear directorios necesarios:
```bash
mkdir -p logs
mkdir -p public/facturas
chmod 755 logs
chmod 755 public/facturas
```

5. Configurar el servidor web para apuntar al directorio `public/`

### Opción 2: Usando Docker (Recomendado)

#### Requisitos Docker
- Docker
- Docker Compose

#### Instalación con Docker

1. Clonar el repositorio:
```bash
git clone <url-del-repositorio>
cd api-facturacion
```

2. Configurar certificados:
   - Colocar el certificado `.crt` en `certs/certificado.crt`
   - Colocar la clave privada `.key` en `certs/rehabilitarte.key`

3. Construir y ejecutar el contenedor:
```bash
# Para producción
docker-compose up -d

# Para desarrollo con Xdebug
docker-compose --profile dev up -d
```

4. La API estará disponible en:
   - Producción: `http://localhost:8080`
   - Desarrollo: `http://localhost:8081`

#### Comandos Docker útiles

```bash
# Ver logs
docker-compose logs -f afip-api

# Ejecutar comandos dentro del contenedor
docker-compose exec afip-api composer install
docker-compose exec afip-api php -v

# Detener servicios
docker-compose down

# Reconstruir imagen
docker-compose build --no-cache

# Verificar estado de los servicios
docker-compose ps
```

## Configuración

Editar `config/config.php` con los datos correspondientes:

```php
define('CUIT_EMISOR', 'TU_CUIT_AQUI');
define('CERT_PATH', __DIR__ . '/../certs/certificado.crt');
define('KEY_PATH', __DIR__ . '/../certs/rehabilitarte.key');
```

## Uso

### Crear una factura

**Endpoint:** `POST /`

**Body (JSON):**
```json
{
    "PtoVta": 1,
    "TipoComp": 1,
    "facCuit": "20123456789",
    "FechaComp": "15/12/2024",
    "facTotal": 1000.00,
    "facPeriodo_inicio": "01/12/2024",
    "facPeriodo_fin": "31/12/2024",
    "fechaUltimoDia": "15/01/2025"
}
```

**Respuesta:**
```json
{
    "success": true,
    "nro": 12345,
    "CAE": "12345678901234",
    "Vencimiento": "20241231",
    "pdfFilename": "rehabilitarte-20241215123456.pdf",
    "downloadLink": "http://localhost/facturas/rehabilitarte-20241215123456.pdf",
    "facturador": {...},
    "facturado": {...}
}
```

### Ejemplo con curl

```bash
curl -X POST http://localhost:8080/ \
  -H "Content-Type: application/json" \
  -d '{
    "PtoVta": 1,
    "TipoComp": 1,
    "facCuit": "20123456789",
    "FechaComp": "15/12/2024",
    "facTotal": 1000.00,
    "facPeriodo_inicio": "01/12/2024",
    "facPeriodo_fin": "31/12/2024",
    "fechaUltimoDia": "15/01/2025"
  }'
```

## Endpoints

| Método | Endpoint      | Descripción                         |
|--------|--------------|-------------------------------------|
| POST   | `/`          | Crear una factura electrónica       |
| GET    | `/health`    | Healthcheck del sistema (status)    |

### Ejemplo: Healthcheck

```bash
curl http://localhost:8080/health
```

**Respuesta esperada:**
```json
{
  "status": "ok",
  "php_version": "8.2.x",
  "config": true,
  "cert": true,
  "key": true,
  "logs_writable": true,
  "facturas_writable": true
}
```

## Características

- ✅ Compatible con PHP 8.2+
- ✅ Tipado estricto en todas las funciones
- ✅ Manejo mejorado de errores
- ✅ Generación automática de PDF
- ✅ Consulta de datos de contribuyentes
- ✅ Autorización automática con AFIP
- ✅ Dockerizado para fácil despliegue
- ✅ Xdebug configurado para desarrollo

## Estructura del Proyecto

```
├── config/
│   └── config.php          # Configuración
├── src/
│   ├── AfipWs.php         # Clase principal para comunicación con AFIP
│   └── FacturaPDF.php     # Generación de PDF
├── public/
│   ├── index.php          # Endpoint principal
│   └── facturas/          # PDFs generados
├── logs/                  # Logs del sistema
├── certs/                 # Certificados AFIP
├── Dockerfile             # Imagen de producción
├── Dockerfile.dev         # Imagen de desarrollo
├── docker-compose.yml     # Orquestación de servicios
└── composer.json
```

## Logs

El sistema genera logs detallados en el directorio `logs/`:
- `wsaa_*.log` - Autenticación con AFIP
- `wsfe_*.log` - Operaciones de facturación
- `a13_*.log` - Consultas de contribuyentes
- `openssl_*.log` - Errores de firma digital

### Ver logs con Docker

```bash
# Ver logs en tiempo real
docker-compose logs -f afip-api

# Ver logs específicos
docker-compose exec afip-api tail -f logs/wsfe_error.log
```

## Desarrollo

### Con Xdebug

1. Ejecutar el contenedor de desarrollo:
```bash
docker-compose --profile dev up -d
```

2. Configurar tu IDE para conectar al puerto 9003

3. La API estará disponible en `http://localhost:8081`

### Testing

```bash
# Ejecutar tests dentro del contenedor
docker-compose exec afip-api vendor/bin/phpunit

# Verificar extensiones PHP
docker-compose exec afip-api php -m
```

## Notas de Actualización a PHP 8.2

- Se agregaron tipos de datos estrictos en todas las funciones
- Se mejoró el manejo de errores con validaciones adicionales
- Se actualizaron las dependencias para compatibilidad
- Se optimizó el código para mejor rendimiento
- Se dockerizó completamente el proyecto

## Troubleshooting

### Problemas comunes con Docker

1. **Puerto ya en uso:**
```bash
# Cambiar puerto en docker-compose.yml
ports:
  - "8082:80"  # Cambiar 8080 por 8082
```

2. **Permisos de certificados:**
```bash
# Verificar permisos
ls -la certs/
chmod 600 certs/*.key
chmod 644 certs/*.crt
```

3. **Logs de Apache:**
```bash
docker-compose exec afip-api tail -f /var/log/apache2/error.log
```

## Licencia

Este proyecto es de uso interno para facturación electrónica con AFIP.