<?php

namespace AfipApi\Controllers;

use AfipApi\Services\WSFEService;
use AfipApi\Services\WSBFEService;
use AfipApi\Services\PDFService;
use Exception;

class FacturacionController extends BaseController
{
    public function handle(): void
    {
        $this->handleOptions();
        
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Limpiar URI de parámetros
        $uri = parse_url($uri, PHP_URL_PATH);
        
        try {
            switch (true) {
                case $uri === '/api/v1/wsfe/factura' && $method === 'POST':
                    $this->crearFacturaWSFE();
                    break;
                    
                case $uri === '/api/v1/wsbfe/autorizar' && $method === 'POST':
                    $this->autorizarWSBFE();
                    break;
                    
                case preg_match('/^\/api\/v1\/wsfe\/comprobante\/(\d+)\/(\d+)\/(\d+)$/', $uri, $matches) && $method === 'GET':
                    $this->consultarComprobanteWSFE((int)$matches[1], (int)$matches[2], (int)$matches[3]);
                    break;
                    
                case preg_match('/^\/api\/v1\/wsbfe\/comprobante\/(\d+)\/(\d+)\/(\d+)$/', $uri, $matches) && $method === 'GET':
                    $this->consultarComprobanteWSBFE((int)$matches[1], (int)$matches[2], (int)$matches[3]);
                    break;
                    
                case $uri === '/api/v1/cuit/consultar' && $method === 'POST':
                    $this->consultarCUIT();
                    break;
                    
                case $uri === '/api/v1/parametros/monedas' && $method === 'GET':
                    $this->getParametrosMonedas();
                    break;
                    
                case $uri === '/api/v1/parametros/tipos-comprobante' && $method === 'GET':
                    $this->getParametrosTiposComprobante();
                    break;
                    
                case $uri === '/api/v1/parametros/tipos-iva' && $method === 'GET':
                    $this->getParametrosTiposIva();
                    break;
                    
                case $uri === '/api/v1/parametros/condicion-iva-receptor' && $method === 'GET':
                    $this->getParametrosCondicionIvaReceptor();
                    break;
                    
                case $uri === '/api/v1/cotizacion' && $method === 'POST':
                    $this->getCotizacion();
                    break;
                    
                case $uri === '/api/v1/health' && $method === 'GET':
                    $this->healthCheck();
                    break;
                    
                default:
                    $this->sendError('Endpoint no encontrado', 404, [
                        'uri' => $uri,
                        'method' => $method,
                        'available_endpoints' => $this->getAvailableEndpoints()
                    ]);
            }
        } catch (Exception $e) {
            $this->logError("Error en endpoint $uri: " . $e->getMessage(), $e);
            $this->sendError('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    private function crearFacturaWSFE(): void
    {
        $data = $this->getJsonInput();
        
        $required = ['PtoVta', 'TipoComp', 'facCuit', 'FechaComp', 'facTotal', 'facPeriodo_inicio', 'facPeriodo_fin', 'fechaUltimoDia'];
        $this->validateRequired($data, $required);
        
        $wsfe = new WSFEService($this->client);
        
        // Consultar datos del emisor y receptor
        $facturadorData = $wsfe->consultarCUIT($this->client->getCuit());
        $facturadoData = $wsfe->consultarCUIT($data['facCuit']);
        
        // Preparar datos para la factura
        $facturaData = array_merge($data, [
            'facturador' => $facturadorData,
            'facturado' => $facturadoData
        ]);
        
        // Crear factura en AFIP
        $this->logInfo("Creando factura WSFE para CUIT: {$data['facCuit']}");
        $result = $wsfe->crearFactura($facturaData);
        
        // Agregar datos adicionales al resultado
        $result['facturador'] = $facturadorData;
        $result['facturado'] = $facturadoData;
        
        // Generar PDF
        $facturaData['CAE'] = $result['CAE'];
        $facturaData['Vencimiento'] = $result['Vencimiento'];
        $facturaData['nro'] = $result['nro'];
        
        $pdfService = new PDFService($this->client);
        $pdfFilename = $pdfService->generarFactura($facturaData);
        
        $result['pdf_filename'] = $pdfFilename;
        $result['download_link'] = $this->generateDownloadLink($pdfFilename);
        
        $this->logInfo("Factura WSFE creada exitosamente - CAE: {$result['CAE']}");
        $this->sendSuccess($result, 201);
    }

    private function autorizarWSBFE(): void
    {
        $data = $this->getJsonInput();
        
        $required = ['Tipo_doc', 'Nro_doc', 'Zona', 'Tipo_cbte', 'Punto_vta', 'Imp_total', 'Fecha_cbte'];
        $this->validateRequired($data, $required);
        
        $wsbfe = new WSBFEService($this->client);
        
        $this->logInfo("Autorizando comprobante WSBFE - Tipo: {$data['Tipo_cbte']}, PtoVta: {$data['Punto_vta']}");
        $result = $wsbfe->authorize($data);
        
        $this->logInfo("Comprobante WSBFE autorizado exitosamente - CAE: {$result['cae']}");
        $this->sendSuccess($result, 201);
    }

    private function consultarComprobanteWSFE(int $ptoVta, int $tipoComp, int $nroComp): void
    {
        $wsfe = new WSFEService($this->client);
        
        $this->logInfo("Consultando comprobante WSFE - PtoVta: $ptoVta, Tipo: $tipoComp, Nro: $nroComp");
        $result = $wsfe->getComprobante($ptoVta, $tipoComp, $nroComp);
        
        $this->sendSuccess($result);
    }

    private function consultarComprobanteWSBFE(int $ptoVta, int $tipoComp, int $nroComp): void
    {
        $wsbfe = new WSBFEService($this->client);
        
        $this->logInfo("Consultando comprobante WSBFE - PtoVta: $ptoVta, Tipo: $tipoComp, Nro: $nroComp");
        $result = $wsbfe->getComprobante($ptoVta, $tipoComp, $nroComp);
        
        $this->sendSuccess($result);
    }

    private function consultarCUIT(): void
    {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['cuit']);
        
        $wsfe = new WSFEService($this->client);
        
        $this->logInfo("Consultando CUIT: {$data['cuit']}");
        $result = $wsfe->consultarCUIT($data['cuit']);
        
        $this->sendSuccess($result);
    }

    private function getParametrosMonedas(): void
    {
        $wsbfe = new WSBFEService($this->client);
        $result = $wsbfe->getParameterMonedas();
        $this->sendSuccess($result);
    }

    private function getParametrosTiposComprobante(): void
    {
        $wsbfe = new WSBFEService($this->client);
        $result = $wsbfe->getParameterTiposComprobante();
        $this->sendSuccess($result);
    }

    private function getParametrosTiposIva(): void
    {
        $wsbfe = new WSBFEService($this->client);
        $result = $wsbfe->getParameterTiposIva();
        $this->sendSuccess($result);
    }

    private function getParametrosCondicionIvaReceptor(): void
    {
        $wsbfe = new WSBFEService($this->client);
        $result = $wsbfe->getParameterCondicionIvaReceptor();
        $this->sendSuccess($result);
    }

    private function getCotizacion(): void
    {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['moneda_id']);
        
        $wsbfe = new WSBFEService($this->client);
        $result = $wsbfe->getCotizacion($data['moneda_id'], $data['fecha'] ?? null);
        
        $this->sendSuccess($result);
    }

    private function healthCheck(): void
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'certificates' => $this->client->hasCertificates(),
            'logs_writable' => is_writable(__DIR__ . '/../../logs/'),
            'facturas_writable' => is_writable(__DIR__ . '/../../public/facturas/'),
            'client_active' => $this->client->isActive()
        ];
        
        $allOk = array_reduce($checks, fn($carry, $check) => $carry && $check, true);
        
        $result = [
            'status' => $allOk ? 'ok' : 'error',
            'checks' => $checks,
            'client' => [
                'name' => $this->client->getName(),
                'cuit' => $this->client->getCuit(),
                'environment' => $this->client->getEnvironment()
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'uptime' => $_SERVER['REQUEST_TIME'] - $_SERVER['REQUEST_TIME_FLOAT'] ?? 0
            ]
        ];
        
        $this->sendSuccess($result, $allOk ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            \AfipApi\Core\Database::getConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function generateDownloadLink(string $filename): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return "$protocol://$host/facturas/$filename";
    }

    private function getAvailableEndpoints(): array
    {
        return [
            'WSFE' => [
                'POST /api/v1/wsfe/factura' => 'Crear factura electrónica',
                'GET /api/v1/wsfe/comprobante/{ptoVta}/{tipoComp}/{nroComp}' => 'Consultar comprobante'
            ],
            'WSBFE' => [
                'POST /api/v1/wsbfe/autorizar' => 'Autorizar bono fiscal electrónico',
                'GET /api/v1/wsbfe/comprobante/{ptoVta}/{tipoComp}/{nroComp}' => 'Consultar comprobante BFE'
            ],
            'Utilidades' => [
                'POST /api/v1/cuit/consultar' => 'Consultar datos de CUIT',
                'GET /api/v1/parametros/monedas' => 'Obtener parámetros de monedas',
                'GET /api/v1/parametros/tipos-comprobante' => 'Obtener tipos de comprobante',
                'GET /api/v1/parametros/tipos-iva' => 'Obtener tipos de IVA',
                'GET /api/v1/parametros/condicion-iva-receptor' => 'Obtener condiciones IVA receptor',
                'POST /api/v1/cotizacion' => 'Consultar cotización de moneda',
                'GET /api/v1/health' => 'Health check del sistema'
            ]
        ];
    }
} 