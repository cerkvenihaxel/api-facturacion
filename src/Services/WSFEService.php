<?php

namespace AfipApi\Services;

use Exception;
use DateTime;

class WSFEService extends BaseAfipService
{
    protected function getServiceUrl(): string
    {
        return $this->client->getEnvironment() === 'prod'
            ? 'https://servicios1.afip.gov.ar/wsfev1/service.asmx'
            : 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx';
    }

    protected function getServiceName(): string
    {
        return 'wsfe';
    }

    public function crearFactura(array $data): array
    {
        $this->validateFacturaData($data);
        
        // Obtener último comprobante
        $ultimoNro = $this->getLastCMP($data['PtoVta'], $data['TipoComp']);
        $proximoNro = $ultimoNro + 1;

        // Validar fechas
        $fechaComp = $this->validateDateFormat($data['FechaComp']);
        $this->validateDateRange($fechaComp);

        $fechaInicio = $this->validateDateFormat($data['facPeriodo_inicio']);
        $fechaFin = $this->validateDateFormat($data['facPeriodo_fin']);
        $fechaVto = $this->validateDateFormat($data['fechaUltimoDia']);

        if ($fechaInicio > $fechaFin) {
            throw new Exception("La fecha de inicio del servicio no puede ser posterior a la de fin");
        }

        // Calcular montos correctamente
        $montos = $this->calcularMontos($data['facTotal'], $data['incluye_iva'] ?? false);

        // Construir request
        $request = [
            'Auth' => $this->getAuthArray(),
            'FeCAEReq' => [
                'FeCabReq' => [
                    'CantReg' => 1,
                    'PtoVta' => (int)$data['PtoVta'],
                    'CbteTipo' => (int)$data['TipoComp']
                ],
                'FeDetReq' => [
                    'FECAEDetRequest' => [
                        'Concepto' => (int)($data['Concepto'] ?? 2), // 2 = Servicios
                        'DocTipo' => (int)($data['DocTipo'] ?? 80), // 80 = CUIT
                        'DocNro' => (int)str_replace('-', '', $data['facCuit']),
                        'CbteDesde' => $proximoNro,
                        'CbteHasta' => $proximoNro,
                        'CbteFch' => $fechaComp->format('Ymd'),
                        'ImpNeto' => $montos['neto'],
                        'ImpIVA' => $montos['iva'],
                        'ImpTotal' => $montos['total'],
                        'ImpOpEx' => 0,
                        'ImpTotConc' => 0,
                        'ImpTrib' => 0,
                        'MonId' => $data['MonId'] ?? 'PES',
                        'MonCotiz' => (float)($data['MonCotiz'] ?? 1)
                    ]
                ]
            ]
        ];

        // Agregar IVA si corresponde
        if ($montos['iva'] > 0) {
            $request['FeCAEReq']['FeDetReq']['FECAEDetRequest']['Iva'] = [
                'AlicIva' => [
                    'Id' => (int)($data['IvaId'] ?? 5), // 5 = 21%
                    'BaseImp' => $montos['neto'],
                    'Importe' => $montos['iva']
                ]
            ];
        }

        // Agregar fechas de servicio si es concepto 2 o 3
        if (in_array($data['Concepto'] ?? 2, [2, 3])) {
            $request['FeCAEReq']['FeDetReq']['FECAEDetRequest']['FchServDesde'] = $fechaInicio->format('Ymd');
            $request['FeCAEReq']['FeDetReq']['FECAEDetRequest']['FchServHasta'] = $fechaFin->format('Ymd');
        }

        // Agregar fecha de vencimiento si es concepto 2 o 3
        if (in_array($data['Concepto'] ?? 2, [2, 3])) {
            $request['FeCAEReq']['FeDetReq']['FECAEDetRequest']['FchVtoPago'] = $fechaVto->format('Ymd');
        }

        // Llamar a AFIP
        $this->log("Solicitud FECAESolicitar: " . json_encode($request, JSON_PRETTY_PRINT), 'debug');
        $response = $this->callSoapMethod('FECAESolicitar', $request);
        $this->log("Respuesta FECAESolicitar: " . json_encode($response, JSON_PRETTY_PRINT), 'debug');

        return $this->parseFacturaResponse($response, $proximoNro);
    }

    public function getLastCMP(int $ptoVta, int $tipoComp): int
    {
        $request = [
            'Auth' => $this->getAuthArray(),
            'PtoVta' => $ptoVta,
            'CbteTipo' => $tipoComp
        ];

        $response = $this->callSoapMethod('FECompUltimoAutorizado', $request);
        
        if (isset($response->FECompUltimoAutorizadoResult->CbteNro)) {
            return (int)$response->FECompUltimoAutorizadoResult->CbteNro;
        }

        return 0;
    }

    public function getComprobante(int $ptoVta, int $tipoComp, int $nroComp): array
    {
        $request = [
            'Auth' => $this->getAuthArray(),
            'FeCompConsReq' => [
                'PtoVta' => $ptoVta,
                'CbteTipo' => $tipoComp,
                'CbteNro' => $nroComp
            ]
        ];

        $response = $this->callSoapMethod('FECompConsultar', $request);
        
        return $this->parseComprobanteResponse($response);
    }

    public function consultarCUIT(string $cuit): array
    {
        // Crear cliente SOAP para padrón A13
        $padronUrl = self::URLS[$this->client->getEnvironment()]['padron'];
        $padronClient = new \SoapClient($padronUrl, [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1
        ]);

        // Login usando el método base que maneja automáticamente los servicios
        $this->login();

        $request = [
            'token' => $this->credentials['ws_sr_padron_a13']['token'],
            'sign' => $this->credentials['ws_sr_padron_a13']['sign'],
            'cuitRepresentada' => $this->client->getCuit(),
            'idPersona' => $cuit
        ];

        try {
            $this->log("Consulta CUIT $cuit: " . json_encode($request, JSON_PRETTY_PRINT), 'debug');
            $result = $padronClient->getPersona($request);
            $this->log("Respuesta consulta CUIT $cuit: " . json_encode($result, JSON_PRETTY_PRINT), 'debug');
            
            if (isset($result->personaReturn->persona)) {
                $datos = $result->personaReturn->persona;
                return [
                    'nombre' => $datos->razonSocial ?? ($datos->nombre . ' ' . $datos->apellido),
                    'domicilio' => $datos->domicilio[0]->direccion ?? 'No disponible',
                    'localidad' => $datos->domicilio[0]->localidad ?? 'No disponible',
                    'provincia' => $datos->domicilio[0]->descripcionProvincia ?? 'No disponible',
                    'codPostal' => $datos->domicilio[0]->codigoPostal ?? 'No disponible',
                    'estado' => $datos->estadoClave ?? 'No disponible',
                    'tipoPersona' => $datos->tipoPersona ?? 'No disponible',
                    'impIVA' => $datos->impuesto ?? []
                ];
            } else {
                throw new Exception("CUIT $cuit no encontrado o sin datos");
            }
        } catch (\SoapFault $e) {
            $this->log("Error consultando CUIT $cuit: " . $e->getMessage(), 'error');
            throw new Exception("Error en consulta de CUIT $cuit: " . $e->getMessage());
        }
    }

    private function validateFacturaData(array $data): void
    {
        $required = ['PtoVta', 'TipoComp', 'facCuit', 'FechaComp', 'facTotal', 'facPeriodo_inicio', 'facPeriodo_fin', 'fechaUltimoDia'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Campo requerido faltante: $field");
            }
        }

        if (!is_numeric($data['facTotal']) || $data['facTotal'] <= 0) {
            throw new Exception("facTotal debe ser un número positivo");
        }

        if (!preg_match('/^\d{11}$/', str_replace('-', '', $data['facCuit']))) {
            throw new Exception("facCuit debe tener 11 dígitos");
        }
    }

    private function calcularMontos(float $total, bool $incluyeIva = false): array
    {
        if ($incluyeIva) {
            // Si el total incluye IVA, calcular neto e IVA
            $totalConIva = $total;
            $neto = round($totalConIva / 1.21, 2);
            $iva = round($totalConIva - $neto, 2);
        } else {
            // Si el total es neto, calcular IVA y total
            $neto = $total;
            $iva = round($neto * 0.21, 2);
            $totalConIva = $neto + $iva;
        }

        return [
            'neto' => $neto,
            'iva' => $iva,
            'total' => $totalConIva
        ];
    }

    private function parseFacturaResponse($response, int $nroComprobante): array
    {
        if (!isset($response->FECAESolicitarResult)) {
            throw new Exception("Respuesta inválida de FECAESolicitar");
        }

        $result = $response->FECAESolicitarResult;

        // Verificar errores generales
        if (isset($result->Errors) && !empty($result->Errors->Err)) {
            $errors = is_array($result->Errors->Err) ? $result->Errors->Err : [$result->Errors->Err];
            $errorMsgs = array_map(fn($err) => "{$err->Code}: {$err->Msg}", $errors);
            throw new Exception("Errores AFIP: " . implode(', ', $errorMsgs));
        }

        if (!isset($result->FeCabResp->Resultado)) {
            throw new Exception("Resultado no encontrado en respuesta AFIP");
        }

        $resultado = $result->FeCabResp->Resultado;
        
        if ($resultado === 'A') {
            // Autorizado
            $detResp = $result->FeDetResp->FECAEDetResponse;
            return [
                'success' => true,
                'resultado' => $resultado,
                'nro' => $nroComprobante,
                'CAE' => $detResp->CAE,
                'Vencimiento' => $detResp->CAEFchVto,
                'observaciones' => $this->parseObservacionesWSFE($detResp),
                'eventos' => $this->parseEventosWSFE($result)
            ];
        } elseif ($resultado === 'R') {
            // Rechazado
            $detResp = $result->FeDetResp->FECAEDetResponse;
            $observaciones = $this->parseObservacionesWSFE($detResp);
            $obsTexto = array_map(fn($obs) => "{$obs['codigo']}: {$obs['mensaje']}", $observaciones);
            throw new Exception("Factura rechazada por AFIP: " . implode(', ', $obsTexto));
        } else {
            // Parcial u otro
            throw new Exception("Resultado inesperado de AFIP: $resultado");
        }
    }

    private function parseComprobanteResponse($response): array
    {
        if (!isset($response->FECompConsultarResult)) {
            throw new Exception("Respuesta inválida de FECompConsultar");
        }

        $result = $response->FECompConsultarResult;

        if (isset($result->Errors) && !empty($result->Errors->Err)) {
            $errors = is_array($result->Errors->Err) ? $result->Errors->Err : [$result->Errors->Err];
            $errorMsgs = array_map(fn($err) => "{$err->Code}: {$err->Msg}", $errors);
            throw new Exception("Errores AFIP: " . implode(', ', $errorMsgs));
        }

        return [
            'success' => true,
            'comprobante' => $result->ResultGet ?? null,
            'eventos' => $this->parseEventosWSFE($result)
        ];
    }

    private function parseObservacionesWSFE($detResp): array
    {
        $observaciones = [];
        if (isset($detResp->Observaciones) && !empty($detResp->Observaciones->Obs)) {
            $obs = is_array($detResp->Observaciones->Obs) ? $detResp->Observaciones->Obs : [$detResp->Observaciones->Obs];
            foreach ($obs as $observacion) {
                $observaciones[] = [
                    'codigo' => $observacion->Code ?? null,
                    'mensaje' => $observacion->Msg ?? null
                ];
            }
        }
        return $observaciones;
    }

    private function parseEventosWSFE($result): array
    {
        $eventos = [];
        if (isset($result->Events) && !empty($result->Events->Evt)) {
            $evts = is_array($result->Events->Evt) ? $result->Events->Evt : [$result->Events->Evt];
            foreach ($evts as $evento) {
                $eventos[] = [
                    'codigo' => $evento->Code ?? null,
                    'mensaje' => $evento->Msg ?? null
                ];
            }
        }
                 return $eventos;
     }
} 