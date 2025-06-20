# Colección Postman - AFIP API Facturación

Esta colección de Postman incluye todos los endpoints disponibles en la API de facturación electrónica con AFIP.

## 📁 Archivos Incluidos

- `AFIP-API-Facturacion.postman_collection.json` - Colección principal con todos los endpoints
- `AFIP-API-Environments.postman_environment.json` - Variables de entorno
- `POSTMAN-README.md` - Este archivo de documentación
- `test-server.sh` - Script para probar el servidor
- `php-server-config.php` - Configuración para servidor de desarrollo PHP

## 🚀 Instalación

### Opción 1: Importar desde Postman Desktop

1. Abrir Postman
2. Hacer clic en **Import** (botón azul en la esquina superior izquierda)
3. Arrastrar y soltar el archivo `AFIP-API-Facturacion.postman_collection.json`
4. Hacer clic en **Import**

### Opción 2: Importar desde URL

1. En Postman, ir a **Import**
2. Seleccionar la pestaña **Link**
3. Pegar la URL del archivo de la colección
4. Hacer clic en **Continue** y luego **Import**

## ⚙️ Configuración

### 1. Configurar Variables de Entorno

La colección utiliza variables de entorno para facilitar el cambio entre diferentes entornos:

#### Variables Disponibles:
- `base_url`: URL base de la API
- `api_version`: Versión de la API (actualmente v1)
- `content_type`: Tipo de contenido (application/json)
- `server_type`: Tipo de servidor (php-dev-server, apache, nginx)

#### Configuraciones por Entorno:

**Servidor de Desarrollo PHP:**
```
base_url: http://localhost:8000
server_type: php-dev-server
```

**Docker Desarrollo:**
```
base_url: http://localhost:8081
server_type: apache
```

**Docker Producción:**
```
base_url: http://localhost:8080
server_type: apache
```

**Producción:**
```
base_url: https://tu-dominio.com
server_type: apache
```

### 2. Configurar Certificados AFIP

Antes de usar la API, asegúrate de tener configurados los certificados AFIP:

1. Colocar el certificado `.crt` en `certs/certificado.crt`
2. Colocar la clave privada `.key` en `certs/rehabilitarte.key`
3. Verificar permisos de escritura en `logs/` y `public/facturas/`

## 🖥️ Configuración del Servidor

### Servidor de Desarrollo PHP (Recomendado para desarrollo)

**Opción 1: Desde el directorio public**
```bash
cd public
php -S localhost:8000
```

**Opción 2: Desde la raíz del proyecto**
```bash
php -S localhost:8000 -t public
```

**Opción 3: Con configuración personalizada**
```bash
php -S localhost:8000 -t public php-server-config.php
```

### Docker (Recomendado para producción)

```bash
# Producción
docker-compose up -d

# Desarrollo con Xdebug
docker-compose --profile dev up -d
```

### Apache/Nginx (Producción)

Configurar el servidor web para apuntar al directorio `public/` y habilitar `mod_rewrite`.

## 📋 Endpoints Disponibles

### 1. Health Check
- **Método:** GET
- **URL:** `{{base_url}}/health`
- **Descripción:** Verifica el estado del sistema y componentes necesarios

### 2. Crear Factura Electrónica
- **Método:** POST
- **URL:** `{{base_url}}/`
- **Descripción:** Crea una factura electrónica con AFIP y genera PDF

### 3. Ejemplos de Facturación
- **Factura A - Servicios:** Para empresas (IVA Responsable Inscripto)
- **Factura B - Consumidor Final:** Para consumidores finales
- **Factura C - Exento:** Para operaciones exentas de IVA

## 🧪 Tests Automáticos

La colección incluye tests automáticos que se ejecutan después de cada request:

### Tests Generales:
- Verificación de códigos de estado HTTP (200/201)
- Validación de headers de respuesta
- Verificación de tipo de contenido JSON

### Tests Específicos:

**Health Check:**
- Validación de estructura de respuesta
- Verificación de propiedades requeridas

**Creación de Facturas:**
- Validación de estructura de respuesta exitosa
- Verificación de propiedades en caso de error

## 📝 Ejemplos de Uso

### Health Check
```bash
curl -X GET "http://localhost:8000/health"
```

### Crear Factura
```bash
curl -X POST "http://localhost:8000/" \
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

## 🔧 Tipos de Comprobante

| Código | Descripción | Uso |
|--------|-------------|-----|
| 1 | Factura A | IVA Responsable Inscripto |
| 6 | Factura B | Consumidor Final |
| 11 | Factura C | Operaciones Exentas |
| 2 | Nota de Débito A | Débitos Factura A |
| 3 | Nota de Crédito A | Créditos Factura A |
| 7 | Nota de Débito B | Débitos Factura B |
| 8 | Nota de Crédito B | Créditos Factura B |
| 12 | Nota de Débito C | Débitos Factura C |
| 13 | Nota de Crédito C | Créditos Factura C |

## 🚨 Códigos de Error

| Código | Descripción |
|--------|-------------|
| 200 | OK - Operación exitosa |
| 201 | Created - Recurso creado |
| 400 | Bad Request - JSON inválido o datos incorrectos |
| 404 | Not Found - Endpoint no encontrado |
| 405 | Method Not Allowed - Método HTTP no permitido |
| 500 | Internal Server Error - Error del servidor |

## 📊 Respuestas de Ejemplo

### Health Check Exitoso
```json
{
  "status": "ok",
  "php_version": "8.2.15",
  "server_type": "PHP Development Server",
  "request_uri": "/health",
  "config": true,
  "cert": true,
  "key": true,
  "logs_writable": true,
  "facturas_writable": true,
  "vendor_exists": true,
  "all_checks_passed": true
}
```

### Factura Creada Exitosamente
```json
{
  "success": true,
  "nro": 12345,
  "CAE": "12345678901234",
  "Vencimiento": "20241231",
  "pdfFilename": "rehabilitarte-20241215123456.pdf",
  "downloadLink": "http://localhost/facturas/rehabilitarte-20241215123456.pdf",
  "facturador": {
    "nombre": "EMPRESA EJEMPLO S.A.",
    "domicilio": "AV. EJEMPLO 123",
    "localidad": "CIUDAD AUTONOMA BUENOS AIRES",
    "provincia": "CAPITAL FEDERAL",
    "impIVA": "IVA Responsable Inscripto"
  },
  "facturado": {
    "nombre": "CLIENTE EJEMPLO S.A.",
    "domicilio": "CALLE CLIENTE 456",
    "localidad": "BUENOS AIRES",
    "provincia": "BUENOS AIRES",
    "impIVA": "IVA Responsable Inscripto"
  }
}
```

## 🔍 Troubleshooting

### Problemas Comunes:

#### 1. Error 404 "Not Found"
**Causa:** El servidor no está configurado correctamente para manejar las rutas.

**Solución:**
- **Para servidor de desarrollo PHP:** Asegúrate de ejecutar desde el directorio `public/`
  ```bash
  cd public && php -S localhost:8000
  ```
- **Para Apache:** Verifica que `mod_rewrite` esté habilitado y el `.htaccess` esté presente
- **Para Docker:** Verifica que el contenedor esté ejecutándose correctamente

#### 2. Error 500 en Health Check
**Causa:** Problemas con certificados o permisos.

**Solución:**
- Verificar que los certificados AFIP estén en su lugar
- Comprobar permisos de escritura en directorios
- Revisar logs de PHP en `logs/php_errors.log`

#### 3. Error 400 en creación de facturas
**Causa:** JSON inválido o campos faltantes.

**Solución:**
- Verificar formato JSON válido
- Comprobar que todos los campos requeridos estén presentes
- Validar tipos de datos (números, fechas, etc.)

#### 4. Error de conexión
**Causa:** Servidor no ejecutándose o URL incorrecta.

**Solución:**
- Verificar que el servidor esté ejecutándose
- Comprobar la URL base en las variables de entorno
- Verificar que el puerto esté disponible

### Script de Prueba

Usa el script incluido para probar el servidor:

```bash
# Probar con configuración por defecto
./test-server.sh

# Probar con IP y puerto específicos
./test-server.sh 192.168.1.100 8000
```

### Logs Útiles:
- `logs/php_errors.log` - Errores de PHP
- `logs/wsaa_*.log` - Logs de autenticación AFIP
- `logs/openssl_error.log` - Errores de certificados
- `logs/a13_request.log` - Consultas de contribuyentes

## 📞 Soporte

Para problemas con la API o la colección de Postman:

1. Ejecutar el script de prueba: `./test-server.sh`
2. Verificar la documentación del proyecto principal
3. Revisar los logs en el directorio `logs/`
4. Ejecutar el health check para diagnosticar problemas
5. Verificar la configuración de certificados AFIP

## 🔄 Actualizaciones

Para mantener la colección actualizada:

1. Descargar la versión más reciente del repositorio
2. Reemplazar los archivos de la colección
3. Verificar que las variables de entorno estén configuradas correctamente
4. Probar los endpoints principales con el script de prueba

---

**Nota:** Esta colección está diseñada para trabajar con la API de facturación AFIP. Asegúrate de tener configurados correctamente los certificados y permisos antes de usar los endpoints de facturación.

**Para desarrollo rápido:** Usa el servidor de desarrollo de PHP con `cd public && php -S localhost:8000` 