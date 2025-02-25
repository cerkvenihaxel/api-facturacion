# Proyecto AFIP API
API en PHP para facturación electrónica con AFIP.

## Requisitos
- PHP 7.4+
- Extensiones: soap, openssl
- Composer
- OpenSSL
- Certificados de AFIP (.crt y .key)

## Instalación
1. Clonar el repositorio.
2. Ejecutar `composer install`.
3. Colocar certificados en `certs/`.
4. Levantar el servidor: `cd public && php -S localhost:8000`.

## Uso
Enviar un POST a `http://localhost:8000` con un JSON como el siguiente:
```json
{
    "PtoVta": "00002",
    "TipoComp": 1,
    "facCuit": "33712504779",
    "FechaComp": "24/02/2025",
    "facTotal": 567000.00,
    "facPeriodo_inicio": "01/01/2025",
    "facPeriodo_fin": "31/01/2025",
    "fechaUltimoDia": "24/02/2025"
}