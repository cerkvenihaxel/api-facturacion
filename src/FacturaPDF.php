<?php
namespace AfipApi;

require_once __DIR__ . '/../vendor/autoload.php';
use FPDF;

class FacturaPDF extends FPDF {
    public function generarFactura($data) {
        $this->AddPage();
        $this->SetFont('Arial', '', 12);

        $this->Cell(0, 10, 'FACTURA', 0, 1, 'C');
        $this->Cell(0, 10, 'ORIGINAL', 0, 1, 'C');
        $this->Ln(10);

        $this->Cell(0, 10, $data['facturador']['nombre'], 0, 1);
        $this->Cell(0, 10, "Domicilio Comercial: {$data['facturador']['domicilio']} - {$data['facturador']['localidad']}, {$data['facturador']['provincia']}", 0, 1);
        $this->Cell(0, 10, "CUIT: 27280873301", 0, 1);
        $this->Cell(0, 10, "Condicion frente al IVA: {$data['facturador']['impIVA']}", 0, 1);
        $this->Ln(10);

        $this->Cell(0, 10, "Razon Social: {$data['facturado']['nombre']}", 0, 1);
        $this->Cell(0, 10, "CUIT: {$data['facCuit']}", 0, 1);
        $this->Cell(0, 10, "Domicilio: {$data['facturado']['domicilio']} - {$data['facturado']['localidad']}, {$data['facturado']['provincia']}", 0, 1);
        $this->Cell(0, 10, "Condicion frente al IVA: {$data['facturado']['impIVA']}", 0, 1);
        $this->Ln(10);

        $this->Cell(0, 10, "Punto de Venta: {$data['PtoVta']}  Comp. Nro: {$data['nro']}", 0, 1);
        $this->Cell(0, 10, "Fecha de Emision: {$data['FechaComp']}", 0, 1);
        $this->Cell(0, 10, "Periodo Facturado Desde: {$data['facPeriodo_inicio']} Hasta: {$data['facPeriodo_fin']}", 0, 1);
        $this->Cell(0, 10, "Fecha de Vto. para el pago: {$data['fechaUltimoDia']}", 0, 1);
        $this->Ln(10);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 10, 'Codigo', 1);
        $this->Cell(80, 10, 'Producto / Servicio', 1);
        $this->Cell(20, 10, 'Cantidad', 1);
        $this->Cell(20, 10, 'U. Medida', 1);
        $this->Cell(30, 10, 'Precio Unit.', 1);
        $this->Cell(20, 10, 'Subtotal', 1);
        $this->Ln();
        $this->SetFont('Arial', '', 10);
        $this->Cell(20, 10, '001', 1);
        $this->Cell(80, 10, 'Desarrollo y mantenimiento de software', 1);
        $this->Cell(20, 10, '1,00', 1);
        $this->Cell(20, 10, 'unidades', 1);
        $this->Cell(30, 10, number_format($data['facTotal'], 2, ',', '.'), 1);
        $this->Cell(20, 10, number_format($data['facTotal'], 2, ',', '.'), 1);
        $this->Ln(20);

        $this->Cell(0, 10, "Subtotal: $" . number_format($data['facTotal'], 2, ',', '.'), 0, 1);
        $this->Cell(0, 10, "Importe Total: $" . number_format($data['facTotal'], 2, ',', '.'), 0, 1);
        $this->Ln(10);

        $this->Cell(0, 10, "CAE Nro: {$data['CAE']}", 0, 1);
        $this->Cell(0, 10, "Fecha de Vto. de CAE: {$data['Vencimiento']}", 0, 1);
        $this->Cell(0, 10, 'Comprobante Autorizado', 0, 1);

        $timestamp = date('YmdHis');
        $filename = "rehabilitarte-$timestamp.pdf";
        $filepath = __DIR__ . '/../public/facturas/' . $filename;

        $this->Output('F', $filepath);
        return $filename;
    }
}