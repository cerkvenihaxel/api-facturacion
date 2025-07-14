<?php

namespace AfipApi\Services;

use Exception;
use DateTime;

class WSBFEService extends BaseAfipService
{
    protected function getServiceUrl(): string
    {
        return $this->client->getEnvironment() === 'prod'
            ? 'https://servicios1.afip.gov.ar/wsbfev1/service.asmx'
            : 'https://wswhomo.afip.gov.ar/wsbfev1/service.asmx';
    }

    protected function getServiceName(): string
    {
        return 'wsbfe';
    }

    public function authorize(array $data): array
    {
        $this->validateAuthData($data);
        
        $comprobante = $this->buildComprobanteData($data);
        
        $params = [
            'Auth' => $this->getAuthArray(),
            'Cmp' => $comprobante
        ];

        $response = $this->callSoapMethod('BFEAuthorize', $params);
        
        return $this->parseAuthResponse($response);
    }

    public function getComprobante(int $ptoVta, int $tipoComp, int $nroComp): array
    {
        $params = [
            'Auth' => $this->getAuthArray(),
            'Tipo_cbte' => $tipoComp,
            'Punto_vta' => $ptoVta,
            'Cbte_nro' => $nroComp
        ];

        $response = $this->callSoapMethod('BFEGetCMP', $params);
        
        return $this->parseGetCMPResponse($response);
    }

    public function getLastComprobante(int $ptoVta, int $tipoComp): int
    {
        $params = [
            'Auth' => $this->getAuthArray(),
            'Tipo_cbte' => $tipoComp,
            'Punto_vta' => $ptoVta
        ];

        $response = $this->callSoapMethod('BFEGetLastCMP', $params);
        
        if (isset($response->BFEGetLastCMPResult->BFEResultGet->Cbte_nro)) {
            return (int)$response->BFEGetLastCMPResult->BFEResultGet->Cbte_nro;
        }

        return 0;
    }

    public function getParameterMonedas(): array
    {
        $params = ['Auth' => $this->getAuthArray()];
        $response = $this->callSoapMethod('BFEGetPARAM_MON', $params);
        
        return $this->parseParameterResponse($response, 'BFEGetPARAM_MONResult');
    }

    public function getParameterTiposComprobante(): array
    {
        $params = ['Auth' => $this->getAuthArray()];
        $response = $this->callSoapMethod('BFEGetPARAM_Tipo_cbte', $params);
        
        return $this->parseParameterResponse($response, 'BFEGetPARAM_Tipo_cbteResult');
    }

    public function getParameterTiposIva(): array
    {
        $params = ['Auth' => $this->getAuthArray()];
        $response = $this->callSoapMethod('BFEGetPARAM_Tipo_Iva', $params);
        
        return $this->parseParameterResponse($response, 'BFEGetPARAM_Tipo_IvaResult');
    }

    public function getParameterCondicionIvaReceptor(): array
    {
        $params = ['Auth' => $this->getAuthArray()];
        $response = $this->callSoapMethod('BFEGetPARAM_CondicionIvaReceptor', $params);
        
        return $this->parseParameterResponse($response, 'BFEGetPARAM_CondicionIvaReceptorResult');
    }

    public function getCotizacion(string $monedaId, ?string $fecha = null): array
    {
        $params = [
            'Auth' => $this->getAuthArray(),
            'MonId' => $monedaId
        ];

        if ($fecha) {
            $params['FchCotiz'] = $fecha;
        }

        $response = $this->callSoapMethod('BFEGetCotizacion', $params);
        
        return $this->parseCotizacionResponse($response);
    }

    private function validateAuthData(array $data): void
    {
        $required = ['Tipo_doc', 'Nro_doc', 'Zona', 'Tipo_cbte', 'Punto_vta', 'Imp_total', 'Fecha_cbte'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Campo requerido faltante: $field");
            }
        }

        // Validar tipos de datos
        if (!is_int($data['Tipo_doc']) || !is_int($data['Nro_doc'])) {
            throw new Exception("Tipo_doc y Nro_doc deben ser números enteros");
        }

        if (!is_numeric($data['Imp_total']) || $data['Imp_total'] <= 0) {
            throw new Exception("Imp_total debe ser un número positivo");
        }

        // Validar fecha
        $this->validateDateFormat($data['Fecha_cbte'], 'Ymd');
    }

    private function buildComprobanteData(array $data): array
    {
        $proximoNro = $this->getLastComprobante($data['Punto_vta'], $data['Tipo_cbte']) + 1;

        $comprobante = [
            'Id' => time(), // ID único del requerimiento
            'Tipo_doc' => (int)$data['Tipo_doc'],
            'Nro_doc' => (int)$data['Nro_doc'],
            'Zona' => (int)$data['Zona'],
            'Tipo_cbte' => (int)$data['Tipo_cbte'],
            'Punto_vta' => (int)$data['Punto_vta'],
            'Cbte_nro' => $proximoNro,
            'Imp_total' => (float)$data['Imp_total'],
            'Imp_tot_conc' => (float)($data['Imp_tot_conc'] ?? 0),
            'Imp_neto' => (float)($data['Imp_neto'] ?? $data['Imp_total']),
            'Impto_liq' => (float)($data['Impto_liq'] ?? 0),
            'Impto_liq_rni' => (float)($data['Impto_liq_rni'] ?? 0),
            'Imp_op_ex' => (float)($data['Imp_op_ex'] ?? 0),
            'Imp_perc' => (float)($data['Imp_perc'] ?? 0),
            'Imp_iibb' => (float)($data['Imp_iibb'] ?? 0),
            'Imp_perc_mun' => (float)($data['Imp_perc_mun'] ?? 0),
            'Imp_internos' => (float)($data['Imp_internos'] ?? 0),
            'Imp_moneda_Id' => $data['Imp_moneda_Id'] ?? 'PES',
            'Imp_moneda_ctz' => (float)($data['Imp_moneda_ctz'] ?? 1),
            'Fecha_cbte' => $data['Fecha_cbte']
        ];

        // Agregar fecha de vencimiento si está presente
        if (isset($data['Fecha_vto_pago'])) {
            $comprobante['Fecha_vto_pago'] = $data['Fecha_vto_pago'];
        }

        // Agregar condición IVA receptor si está presente
        if (isset($data['CondicionIVAReceptorId'])) {
            $comprobante['CondicionIVAReceptorId'] = (int)$data['CondicionIVAReceptorId'];
        }

        // Agregar indicador de misma moneda extranjera si está presente
        if (isset($data['CanMisMonExt'])) {
            $comprobante['CanMisMonExt'] = $data['CanMisMonExt'];
        }

        // Agregar items si están presentes
        if (isset($data['Items']) && is_array($data['Items'])) {
            $comprobante['Items'] = ['Item' => []];
            foreach ($data['Items'] as $item) {
                $comprobante['Items']['Item'][] = [
                    'Pro_codigo_ncm' => $item['Pro_codigo_ncm'] ?? '',
                    'Pro_codigo_sec' => $item['Pro_codigo_sec'] ?? '',
                    'Pro_ds' => $item['Pro_ds'],
                    'Pro_qty' => (float)$item['Pro_qty'],
                    'Pro_umed' => (int)$item['Pro_umed'],
                    'Pro_precio_uni' => (float)$item['Pro_precio_uni'],
                    'Imp_bonif' => (float)($item['Imp_bonif'] ?? 0),
                    'Imp_total' => (float)$item['Imp_total'],
                    'Iva_id' => (int)$item['Iva_id']
                ];
            }
        }

        // Agregar comprobantes asociados si están presentes
        if (isset($data['CbtesAsoc']) && is_array($data['CbtesAsoc'])) {
            $comprobante['CbtesAsoc'] = ['CbteAsoc' => []];
            foreach ($data['CbtesAsoc'] as $cbteAsoc) {
                $comprobante['CbtesAsoc']['CbteAsoc'][] = [
                    'Tipo_cbte' => (int)$cbteAsoc['Tipo_cbte'],
                    'Punto_vta' => (int)$cbteAsoc['Punto_vta'],
                    'Cbte_nro' => (int)$cbteAsoc['Cbte_nro'],
                    'Cuit' => $cbteAsoc['Cuit'] ?? '',
                    'Fecha_cbte' => $cbteAsoc['Fecha_cbte'] ?? ''
                ];
            }
        }

        // Agregar opcionales si están presentes
        if (isset($data['Opcionales']) && is_array($data['Opcionales'])) {
            $comprobante['Opcionales'] = ['Opcional' => []];
            foreach ($data['Opcionales'] as $opcional) {
                $comprobante['Opcionales']['Opcional'][] = [
                    'Id' => $opcional['Id'],
                    'Valor' => $opcional['Valor']
                ];
            }
        }

        return $comprobante;
    }

    private function parseAuthResponse($response): array
    {
        if (!isset($response->BFEAuthorizeResult)) {
            throw new Exception("Respuesta inválida de BFEAuthorize");
        }

        $result = $response->BFEAuthorizeResult;

        // Verificar errores
        if (isset($result->BFEErr) && $result->BFEErr->ErrCode != 0) {
            throw new Exception("Error AFIP: {$result->BFEErr->ErrCode} - {$result->BFEErr->Errmsg}");
        }

        if (!isset($result->BFEResultAuth)) {
            throw new Exception("BFEResultAuth no encontrado en la respuesta");
        }

        $authResult = $result->BFEResultAuth;

        return [
            'success' => true,
            'id' => $authResult->Id ?? null,
            'cae' => $authResult->Cae ?? null,
            'fecha_vencimiento_cae' => $authResult->Fch_venc_Cae ?? null,
            'cbte_nro' => $authResult->Cbte_nro ?? null,
            'punto_vta' => $authResult->Punto_vta ?? null,
            'tipo_cbte' => $authResult->Tipo_cbte ?? null,
            'fecha_cbte' => $authResult->Fecha_cbte ?? null,
            'imp_total' => $authResult->Imp_total ?? null,
            'observaciones' => $this->parseObservaciones($result),
            'eventos' => $this->parseEventos($result)
        ];
    }

    private function parseGetCMPResponse($response): array
    {
        if (!isset($response->BFEGetCMPResult)) {
            throw new Exception("Respuesta inválida de BFEGetCMP");
        }

        $result = $response->BFEGetCMPResult;

        // Verificar errores
        if (isset($result->BFEErr) && $result->BFEErr->ErrCode != 0) {
            throw new Exception("Error AFIP: {$result->BFEErr->ErrCode} - {$result->BFEErr->Errmsg}");
        }

        return [
            'success' => true,
            'comprobante' => $result->BFEResultGet ?? null,
            'eventos' => $this->parseEventos($result)
        ];
    }

    private function parseParameterResponse($response, string $resultKey): array
    {
        if (!isset($response->$resultKey)) {
            throw new Exception("Respuesta inválida de parámetros");
        }

        $result = $response->$resultKey;

        // Verificar errores
        if (isset($result->BFEErr) && $result->BFEErr->ErrCode != 0) {
            throw new Exception("Error AFIP: {$result->BFEErr->ErrCode} - {$result->BFEErr->Errmsg}");
        }

        return [
            'success' => true,
            'data' => $result->BFEResultGet ?? [],
            'eventos' => $this->parseEventos($result)
        ];
    }

    private function parseCotizacionResponse($response): array
    {
        if (!isset($response->BFEGetCotizacionResult)) {
            throw new Exception("Respuesta inválida de BFEGetCotizacion");
        }

        $result = $response->BFEGetCotizacionResult;

        // Verificar errores
        if (isset($result->BFEErr) && $result->BFEErr->ErrCode != 0) {
            throw new Exception("Error AFIP: {$result->BFEErr->ErrCode} - {$result->BFEErr->Errmsg}");
        }

        return [
            'success' => true,
            'moneda_id' => $result->BFEResultGet->MonId ?? null,
            'cotizacion' => $result->BFEResultGet->MonCotiz ?? null,
            'fecha_cotizacion' => $result->BFEResultGet->FchCotiz ?? null,
            'eventos' => $this->parseEventos($result)
        ];
    }

    private function parseObservaciones($result): array
    {
        $observaciones = [];
        if (isset($result->BFEResultAuth->Obs)) {
            if (is_array($result->BFEResultAuth->Obs)) {
                foreach ($result->BFEResultAuth->Obs as $obs) {
                    $observaciones[] = [
                        'codigo' => $obs->Code ?? null,
                        'mensaje' => $obs->Msg ?? null
                    ];
                }
            } else {
                $observaciones[] = [
                    'codigo' => $result->BFEResultAuth->Obs->Code ?? null,
                    'mensaje' => $result->BFEResultAuth->Obs->Msg ?? null
                ];
            }
        }
        return $observaciones;
    }

    private function parseEventos($result): array
    {
        $eventos = [];
        if (isset($result->BFEEvents)) {
            if (is_array($result->BFEEvents)) {
                foreach ($result->BFEEvents as $event) {
                    $eventos[] = [
                        'codigo' => $event->EventCode ?? null,
                        'mensaje' => $event->EventMsg ?? null
                    ];
                }
            } else {
                $eventos[] = [
                    'codigo' => $result->BFEEvents->EventCode ?? null,
                    'mensaje' => $result->BFEEvents->EventMsg ?? null
                ];
            }
        }
        return $eventos;
    }
} 