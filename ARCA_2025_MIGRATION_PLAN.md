# Plan de Migración ARCA 2025
## Resolución General N° 5616 - Cambios en Facturación Electrónica

### Información General
- **Implementación**: 2025
- **Afectación**: Webservices WSFE y WSBFE
- **Tipo de cambios**: Obligatorios para cumplimiento normativo

### Cambios Principales Identificados

#### 1. Condición Frente al IVA del Receptor
**Impacto**: CRÍTICO
- Se requiere incluir obligatoriamente la condición frente al IVA del receptor
- Afecta todos los comprobantes electrónicos
- Requiere validación previa de datos del receptor

**Campos a Implementar**:
```json
{
  "receptor": {
    "condicion_iva": "string", // Nuevo campo obligatorio
    "codigo_condicion": "integer", // Código ARCA correspondiente
    "validacion_padron": "boolean" // Validación contra padrón ARCA
  }
}
```

#### 2. Operaciones en Moneda Extranjera
**Impacto**: ALTO
- Nuevo tratamiento para operaciones en moneda extranjera
- Posibles cambios en cotizaciones y conversiones
- Validaciones adicionales para compliance

**Consideraciones**:
- Actualización de tablas de cotizaciones
- Nuevas validaciones de montos
- Integración con servicios de cotización ARCA

#### 3. Modificaciones en Webservices
**Impacto**: MEDIO-ALTO
- Cambios en estructura de requests/responses WSFE/WSBFE
- Posibles nuevos métodos o parámetros
- Versionado de servicios

### Plan de Implementación

#### Fase 1: Análisis y Preparación (Q1 2025)
1. **Obtención de documentación oficial**
   - Resolución General N° 5616 completa
   - Documentación técnica ARCA
   - Casos de uso y ejemplos

2. **Análisis de impacto**
   - Mapeo de cambios en base de datos
   - Identificación de servicios afectados
   - Evaluación de compatibilidad hacia atrás

#### Fase 2: Desarrollo y Testing (Q2 2025)
1. **Actualización de arquitectura**
   - Modificación esquema de base de datos
   - Actualización servicios WSFE/WSBFE
   - Implementación de validaciones

2. **Testing exhaustivo**
   - Tests unitarios para nuevos campos
   - Tests de integración con webservices
   - Validación en ambiente de testing ARCA

#### Fase 3: Migración Gradual (Q3-Q4 2025)
1. **Rollout controlado**
   - Versionado API para transición
   - Migración progresiva de clientes
   - Monitoreo y soporte

### Impacto en la API Actual

#### Servicios Afectados
```php
// WSFEService.php - Requiere actualización
public function generarFactura($datos) {
    // Agregar validación condición IVA receptor
    // Implementar nuevas validaciones moneda extranjera
}

// WSBFEService.php - Requiere actualización  
public function generarBondedFactura($datos) {
    // Aplicar mismos cambios que WSFE
}
```

#### Base de Datos - Nuevas Tablas/Campos
```sql
-- Agregar campos a tabla invoices
ALTER TABLE invoices ADD COLUMN receptor_condicion_iva VARCHAR(50);
ALTER TABLE invoices ADD COLUMN receptor_codigo_condicion INT;
ALTER TABLE invoices ADD COLUMN validacion_padron_realizada BOOLEAN DEFAULT FALSE;

-- Nueva tabla para condiciones IVA
CREATE TABLE condiciones_iva (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo INT UNIQUE NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Endpoints API - Nuevos Campos
```json
// POST /api/v2/invoices (nueva versión)
{
  "client_id": "string",
  "receptor": {
    "cuit": "string",
    "razon_social": "string",
    "condicion_iva": "string", // NUEVO OBLIGATORIO
    "codigo_condicion": "integer" // NUEVO OBLIGATORIO
  },
  "items": [...],
  "moneda": {
    "codigo": "string",
    "cotizacion": "number", // Validaciones mejoradas
    "fecha_cotizacion": "date" // NUEVO para moneda extranjera
  }
}
```

### Configuración Multi-tenant Actualizada

#### Variables de Configuración por Cliente
```php
// config/arca_2025.php
return [
    'validacion_condicion_iva' => env('ARCA_VALIDACION_IVA', true),
    'validacion_padron_automatica' => env('ARCA_VALIDACION_PADRON', true),
    'moneda_extranjera_strict' => env('ARCA_MONEDA_STRICT', true),
    'version_webservice' => env('ARCA_WS_VERSION', '2025.1'),
];
```

### Estrategia de Migración para Clientes

#### Opción 1: Migración Automática
- Actualización automática de estructura
- Validación de datos existentes
- Migración de condiciones IVA por defecto

#### Opción 2: Migración Manual
- Cliente controla el timing
- Validación y corrección de datos
- Testing en ambiente de pruebas

### Cronograma Estimado

| Fase | Periodo | Actividades |
|------|---------|-------------|
| Análisis | Ene-Feb 2025 | Documentación oficial, análisis impacto |
| Desarrollo | Mar-May 2025 | Implementación, testing |
| Testing | Jun-Jul 2025 | QA, validación con ARCA |
| Migración | Ago-Nov 2025 | Rollout gradual |
| Soporte | Dic 2025+ | Soporte post-migración |

### Consideraciones de Compatibilidad

#### Versionado API
- **v1**: Mantener para compatibilidad hasta migración completa
- **v2**: Nueva versión con cumplimiento ARCA 2025
- **Headers**: `X-ARCA-Compliance-Version: 2025`

#### Fallback y Contingencia
- Modo de compatibilidad para datos legacy
- Validaciones progresivas (warnings → errors)
- Rollback plan en caso de problemas

### Próximos Pasos Inmediatos

1. **Monitoreo normativo**
   - Revisar sitio ARCA semanalmente
   - Suscribirse a boletines oficiales
   - Contacto con proveedores de webservices

2. **Preparación técnica**
   - Crear branch `arca-2025` en repositorio
   - Configurar ambiente de testing
   - Documentar arquitectura actual

3. **Comunicación con clientes**
   - Newsletter sobre cambios normativos
   - Timeline de migración
   - Soporte técnico especializado

### Recursos Necesarios

#### Técnicos
- 1-2 desarrolladores senior PHP
- 1 especialista en integración ARCA
- QA specialist para testing compliance

#### Infraestructura
- Ambiente de testing adicional
- Herramientas de migración de datos
- Monitoreo mejorado

### Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Cambios tardíos en spec | Media | Alto | Arquitectura flexible, versionado |
| Incompatibilidad datos legacy | Alta | Medio | Scripts de migración, validación |
| Timing ajustado | Media | Alto | Comenzar desarrollo anticipado |

---

**Documento actualizable**: Este plan se actualizará conforme se publique información oficial sobre la RG N° 5616.

**Contacto técnico**: Para consultas sobre esta migración, contactar al equipo de desarrollo. 