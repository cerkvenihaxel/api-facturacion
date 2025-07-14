<?php

namespace AfipApi\Services;

use AfipApi\Core\Client;
use FPDF;
use Exception;

class PDFService extends FPDF
{
    private Client $client;
    private array $facturaData;
    private string $logoPath;

    public function __construct(Client $client)
    {
        parent::__construct();
        $this->client = $client;
        $this->logoPath = __DIR__ . '/../../public/assets/logo.png';
    }

    public function generarFactura(array $data): string
    {
        $this->facturaData = $data;
        
        $this->AddPage();
        $this->SetAutoPageBreak(true, 15);
        
        // Configurar fuentes
        $this->SetFont('Arial', '', 10);
        
        // Generar secciones
        $this->generarEncabezado();
        $this->generarInfoEmisor();
        $this->generarInfoReceptor();
        $this->generarDetalleComprobante();
        $this->generarItemsFactura();
        $this->generarTotales();
        $this->generarCAE();
        $this->generarPie();
        
        // Generar archivo
        $timestamp = date('YmdHis');
        $filename = "factura_{$this->client->getCuit()}_{$timestamp}.pdf";
        $filepath = __DIR__ . '/../../public/facturas/' . $filename;
        
        // Crear directorio si no existe
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $this->Output('F', $filepath);
        return $filename;
    }

    private function generarEncabezado(): void
    {
        // Logo (si existe)
        if (file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 10, 10, 30);
        }

        // Tipo de comprobante
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY(120, 10);
        $this->Cell(70, 10, $this->getTipoComprobanteTexto(), 1, 0, 'C');
        
        // Código de comprobante
        $this->SetXY(120, 20);
        $this->SetFont('Arial', '', 12);
        $this->Cell(70, 8, 'Cod. ' . str_pad($this->facturaData['TipoComp'] ?? '', 2, '0', STR_PAD_LEFT), 1, 0, 'C');
        
        // Número de comprobante
        $this->SetXY(120, 28);
        $this->SetFont('Arial', 'B', 12);
        $puntoVenta = str_pad($this->facturaData['PtoVta'] ?? '', 5, '0', STR_PAD_LEFT);
        $nroComprobante = str_pad($this->facturaData['nro'] ?? '', 8, '0', STR_PAD_LEFT);
        $this->Cell(70, 8, "N° $puntoVenta-$nroComprobante", 1, 0, 'C');
        
        $this->Ln(30);
    }

    private function generarInfoEmisor(): void
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, $this->facturaData['facturador']['nombre'] ?? 'Nombre no disponible', 0, 1);
        
        $this->SetFont('Arial', '', 10);
        
        // CUIT
        $this->Cell(40, 6, 'CUIT:', 0, 0);
        $this->Cell(0, 6, $this->client->getCuit(), 0, 1);
        
        // Domicilio
        $facturador = $this->facturaData['facturador'] ?? [];
        $domicilio = ($facturador['domicilio'] ?? '') . ', ' . 
                    ($facturador['localidad'] ?? '') . ', ' . 
                    ($facturador['provincia'] ?? '');
        $this->Cell(40, 6, 'Domicilio:', 0, 0);
        $this->Cell(0, 6, $domicilio, 0, 1);
        
        // Condición IVA
        $this->Cell(40, 6, 'Condición IVA:', 0, 0);
        $this->Cell(0, 6, $this->getCondicionIVA($facturador['impIVA'] ?? ''), 0, 1);
        
        $this->Ln(5);
    }

    private function generarInfoReceptor(): void
    {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'DATOS DEL CLIENTE', 0, 1);
        
        $this->SetFont('Arial', '', 10);
        
        $facturado = $this->facturaData['facturado'] ?? [];
        
        // Razón Social
        $this->Cell(40, 6, 'Razón Social:', 0, 0);
        $this->Cell(0, 6, $facturado['nombre'] ?? 'No disponible', 0, 1);
        
        // CUIT
        $this->Cell(40, 6, 'CUIT:', 0, 0);
        $this->Cell(0, 6, $this->facturaData['facCuit'] ?? '', 0, 1);
        
        // Domicilio
        $domicilioCliente = ($facturado['domicilio'] ?? '') . ', ' . 
                           ($facturado['localidad'] ?? '') . ', ' . 
                           ($facturado['provincia'] ?? '');
        $this->Cell(40, 6, 'Domicilio:', 0, 0);
        $this->Cell(0, 6, $domicilioCliente, 0, 1);
        
        // Condición IVA
        $this->Cell(40, 6, 'Condición IVA:', 0, 0);
        $this->Cell(0, 6, $this->getCondicionIVA($facturado['impIVA'] ?? ''), 0, 1);
        
        $this->Ln(5);
    }

    private function generarDetalleComprobante(): void
    {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'DETALLE DEL COMPROBANTE', 0, 1);
        
        $this->SetFont('Arial', '', 10);
        
        // Fecha de emisión
        $this->Cell(50, 6, 'Fecha de Emisión:', 0, 0);
        $this->Cell(70, 6, $this->facturaData['FechaComp'] ?? '', 0, 0);
        
        // Fecha de vencimiento
        $this->Cell(50, 6, 'Fecha de Vto. pago:', 0, 0);
        $this->Cell(0, 6, $this->facturaData['fechaUltimoDia'] ?? '', 0, 1);
        
        // Período facturado
        if (isset($this->facturaData['facPeriodo_inicio']) && isset($this->facturaData['facPeriodo_fin'])) {
            $this->Cell(50, 6, 'Período facturado:', 0, 0);
            $periodo = "Desde {$this->facturaData['facPeriodo_inicio']} hasta {$this->facturaData['facPeriodo_fin']}";
            $this->Cell(0, 6, $periodo, 0, 1);
        }
        
        $this->Ln(5);
    }

    private function generarItemsFactura(): void
    {
        $this->SetFont('Arial', 'B', 10);
        
        // Encabezados de tabla
        $this->Cell(20, 8, 'Código', 1, 0, 'C');
        $this->Cell(80, 8, 'Descripción', 1, 0, 'C');
        $this->Cell(20, 8, 'Cantidad', 1, 0, 'C');
        $this->Cell(25, 8, 'Precio Unit.', 1, 0, 'C');
        $this->Cell(20, 8, 'IVA %', 1, 0, 'C');
        $this->Cell(25, 8, 'Importe', 1, 1, 'C');
        
        $this->SetFont('Arial', '', 9);
        
        // Verificar si hay items específicos
        if (isset($this->facturaData['Items']) && is_array($this->facturaData['Items'])) {
            foreach ($this->facturaData['Items'] as $item) {
                $this->Cell(20, 8, $item['codigo'] ?? '001', 1, 0, 'C');
                $this->Cell(80, 8, substr($item['descripcion'] ?? '', 0, 40), 1, 0, 'L');
                $this->Cell(20, 8, number_format($item['cantidad'] ?? 1, 2), 1, 0, 'C');
                $this->Cell(25, 8, '$' . number_format($item['precio_unitario'] ?? 0, 2), 1, 0, 'R');
                $this->Cell(20, 8, ($item['iva_porcentaje'] ?? 21) . '%', 1, 0, 'C');
                $this->Cell(25, 8, '$' . number_format($item['importe'] ?? 0, 2), 1, 1, 'R');
            }
        } else {
            // Item por defecto (servicios)
            $descripcion = $this->facturaData['descripcion_servicio'] ?? 'Servicios profesionales';
            $total = $this->facturaData['facTotal'] ?? 0;
            
            $this->Cell(20, 8, '001', 1, 0, 'C');
            $this->Cell(80, 8, $descripcion, 1, 0, 'L');
            $this->Cell(20, 8, '1,00', 1, 0, 'C');
            $this->Cell(25, 8, '$' . number_format($total, 2), 1, 0, 'R');
            $this->Cell(20, 8, '21%', 1, 0, 'C');
            $this->Cell(25, 8, '$' . number_format($total, 2), 1, 1, 'R');
        }
        
        $this->Ln(5);
    }

    private function generarTotales(): void
    {
        $facTotal = (float)($this->facturaData['facTotal'] ?? 0);
        $incluyeIva = $this->facturaData['incluye_iva'] ?? false;
        
        if ($incluyeIva) {
            $totalConIva = $facTotal;
            $neto = round($totalConIva / 1.21, 2);
            $iva = round($totalConIva - $neto, 2);
        } else {
            $neto = $facTotal;
            $iva = round($neto * 0.21, 2);
            $totalConIva = $neto + $iva;
        }
        
        // Alinear a la derecha
        $this->SetX(120);
        
        $this->SetFont('Arial', '', 10);
        
        // Subtotal
        $this->Cell(45, 6, 'Subtotal:', 0, 0, 'R');
        $this->Cell(25, 6, '$' . number_format($neto, 2), 1, 1, 'R');
        $this->SetX(120);
        
        // IVA
        $this->Cell(45, 6, 'IVA 21%:', 0, 0, 'R');
        $this->Cell(25, 6, '$' . number_format($iva, 2), 1, 1, 'R');
        $this->SetX(120);
        
        // Total
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(45, 8, 'TOTAL:', 0, 0, 'R');
        $this->Cell(25, 8, '$' . number_format($totalConIva, 2), 1, 1, 'R');
        
        $this->Ln(5);
    }

    private function generarCAE(): void
    {
        if (isset($this->facturaData['CAE'])) {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, 'INFORMACIÓN AFIP', 0, 1);
            
            $this->SetFont('Arial', '', 10);
            
            // CAE
            $this->Cell(30, 6, 'CAE N°:', 0, 0);
            $this->Cell(60, 6, $this->facturaData['CAE'], 0, 0);
            
            // Fecha vencimiento CAE
            $this->Cell(40, 6, 'Fecha Vto. CAE:', 0, 0);
            $this->Cell(0, 6, $this->formatearFechaCAE($this->facturaData['Vencimiento'] ?? ''), 0, 1);
            
            $this->Ln(2);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 6, 'COMPROBANTE AUTORIZADO', 0, 1, 'C');
        }
        
        $this->Ln(5);
    }

    private function generarPie(): void
    {
        $this->SetY(-30);
        $this->SetFont('Arial', 'I', 8);
        
        // Línea separadora
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
        
        // Información del sistema
        $this->Cell(0, 4, 'Documento generado electrónicamente', 0, 1, 'C');
        $this->Cell(0, 4, 'Cliente: ' . $this->client->getName(), 0, 1, 'C');
        $this->Cell(0, 4, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    }

    private function getTipoComprobanteTexto(): string
    {
        $tipos = [
            1 => 'FACTURA A',
            2 => 'NOTA DE DÉBITO A',
            3 => 'NOTA DE CRÉDITO A',
            6 => 'FACTURA B',
            7 => 'NOTA DE DÉBITO B',
            8 => 'NOTA DE CRÉDITO B',
            11 => 'FACTURA C',
            12 => 'NOTA DE DÉBITO C',
            13 => 'NOTA DE CRÉDITO C'
        ];
        
        $tipo = $this->facturaData['TipoComp'] ?? 0;
        return $tipos[$tipo] ?? 'COMPROBANTE';
    }

    private function getCondicionIVA($impuesto): string
    {
        if (is_array($impuesto)) {
            // Si es array, buscar IVA
            foreach ($impuesto as $imp) {
                if (isset($imp->idImpuesto) && $imp->idImpuesto == 30) {
                    return 'Responsable Inscripto';
                }
            }
        }
        
        // Valores por defecto según condición
        $condiciones = [
            'RI' => 'Responsable Inscripto',
            'MT' => 'Monotributista',
            'EX' => 'Exento',
            'CF' => 'Consumidor Final'
        ];
        
        return $condiciones[$impuesto] ?? 'No categorizado';
    }

    private function formatearFechaCAE(string $fecha): string
    {
        if (strlen($fecha) == 8) {
            return substr($fecha, 6, 2) . '/' . substr($fecha, 4, 2) . '/' . substr($fecha, 0, 4);
        }
        return $fecha;
    }
} 