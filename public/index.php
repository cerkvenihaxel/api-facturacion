<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
use AfipApi\AfipWs;
use AfipApi\FacturaPDF;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    try {
        $afip = new AfipWs(CUIT_EMISOR, CERT_PATH, KEY_PATH);

        // Consultar facturador
        try {
            $facturadorData = $afip->consultarCUIT('27280873301');
        } catch (\Exception $e) {
            file_put_contents(LOG_DIR . 'facturador_error.log', "Error al consultar facturador (27280873301): " . $e->getMessage());
            $facturadorData = ['error' => $e->getMessage()];
        }

        // Consultar facturado
        try {
            $facturadoData = $afip->consultarCUIT($input['facCuit']);
        } catch (\Exception $e) {
            file_put_contents(LOG_DIR . 'facturado_error.log', "Error al consultar facturado ({$input['facCuit']}): " . $e->getMessage());
            $facturadoData = ['error' => $e->getMessage()];
        }

        $lastCMP = $afip->getLastCMP($input['PtoVta'], $input['TipoComp']);
        $nro = $lastCMP + 1;

        $facturaData = [
            'PtoVta' => $input['PtoVta'],
            'TipoComp' => $input['TipoComp'],
            'facCuit' => $input['facCuit'],
            'nro' => $nro,
            'FechaComp' => $input['FechaComp'],
            'facTotal' => $input['facTotal'],
            'facPeriodo_inicio' => $input['facPeriodo_inicio'],
            'facPeriodo_fin' => $input['facPeriodo_fin'],
            'fechaUltimoDia' => $input['fechaUltimoDia'],
            'facturador' => $facturadorData,
            'facturado' => $facturadoData
        ];

        $result = $afip->crearFactura($facturaData);
        $pdf = new FacturaPDF();
        $facturaData['CAE'] = $result['CAE'];
        $facturaData['Vencimiento'] = $result['Vencimiento'];
        $pdfFilename = $pdf->generarFactura($facturaData);

        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/facturas/';
        $downloadLink = $baseUrl . $pdfFilename;

        echo json_encode([
            'success' => true,
            'nro' => $nro,
            'CAE' => $result['CAE'],
            'Vencimiento' => $result['Vencimiento'],
            'pdfFilename' => $pdfFilename,
            'downloadLink' => $downloadLink,
            'facturador' => $facturadorData,
            'facturado' => $facturadoData
        ]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}