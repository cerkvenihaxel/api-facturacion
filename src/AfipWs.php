<?php
namespace AfipApi;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

class AfipWs {
    private $cuit;
    private $cert;
    private $key;
    private $wsaaUrl;
    private $wswUrl;
    private $padronUrl;
    private $credentials = []; // Arreglo para almacenar token/sign por servicio
    private $client;
    private $padronClient;

    public function __construct($cuit, $certPath, $keyPath) {
        $this->cuit = $cuit;
        $this->cert = $certPath;
        $this->key = $keyPath;
        $this->wsaaUrl = URLWSAA_PROD;
        $this->wswUrl = URLWSW_PROD;
        $this->padronUrl = URLWSPADRON_PROD;
        $this->login('wsfe'); // Login inicial para wsfe
        $this->initSoapClients();
    }

    private function login($service) {
        $tra = $this->createTRA($service);
        $cms = $this->signTRA($tra);
        $response = $this->callWSAA($cms);

        $xmlResponse = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xmlResponse === false) {
            file_put_contents(LOG_DIR . "wsaa_response_error_$service.log", "Error al cargar XML: " . implode("\n", libxml_get_errors()) . "\nRespuesta cruda:\n" . $response);
            throw new \Exception("Error al cargar el XML de respuesta del WSAA para $service. Revisa logs/wsaa_response_error_$service.log");
        }

        $namespaces = $xmlResponse->getNamespaces(true);
        $body = $xmlResponse->children($namespaces['soapenv'])->Body;

        if (isset($body->Fault)) {
            $faultString = (string)$body->Fault->faultstring;
            file_put_contents(LOG_DIR . "wsaa_fault_$service.log", "Fault recibido del WSAA: $faultString\nRespuesta completa:\n" . $response);
            throw new \Exception("Error del WSAA para $service: $faultString. Revisa logs/wsaa_fault_$service.log");
        }

        $loginCmsResponse = $body->children()->loginCmsResponse;
        if (!$loginCmsResponse || empty($loginCmsResponse->loginCmsReturn)) {
            file_put_contents(LOG_DIR . "wsaa_response_error_$service.log", "Tag 'loginCmsReturn' no encontrado\nRespuesta completa:\n" . $response);
            throw new \Exception("Error: El tag 'loginCmsReturn' no se encuentra en la respuesta del WSAA para $service. Revisa logs/wsaa_response_error_$service.log");
        }

        $loginCmsReturn = (string)$loginCmsResponse->loginCmsReturn;
        $loginTicketResponse = simplexml_load_string(html_entity_decode($loginCmsReturn));
        if ($loginTicketResponse === false) {
            file_put_contents(LOG_DIR . "wsaa_response_error_$service.log", "Error al cargar XML decodificado: " . implode("\n", libxml_get_errors()) . "\nloginCmsReturn:\n" . $loginCmsReturn);
            throw new \Exception("Error al cargar el XML decodificado del WSAA para $service. Revisa logs/wsaa_response_error_$service.log");
        }

        $this->credentials[$service] = [
            'token' => (string)$loginTicketResponse->credentials->token,
            'sign' => (string)$loginTicketResponse->credentials->sign
        ];
        file_put_contents(LOG_DIR . "wsaa_success_$service.log", "Login exitoso para $service\nToken: {$this->credentials[$service]['token']}\nSign: {$this->credentials[$service]['sign']}");
    }

    private function createTRA($service) {
        $timezone = new \DateTimeZone('America/Argentina/Buenos_Aires');
        $now = new \DateTime('now', $timezone);

        $genTime = clone $now;
        $genTime->modify('-10 minutes');
        $expTime = clone $now;
        $expTime->modify('+10 minutes');

        $genTime->setTimezone(new \DateTimeZone('UTC'));
        $expTime->setTimezone(new \DateTimeZone('UTC'));

        $uniqueId = $now->format('U');
        $genTimeStr = $genTime->format('c');
        $expTimeStr = $expTime->format('c');

        $tra = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><loginTicketRequest version="1.0"></loginTicketRequest>');
        $tra->addChild('header');
        $tra->header->addChild('uniqueId', $uniqueId);
        $tra->header->addChild('generationTime', $genTimeStr);
        $tra->header->addChild('expirationTime', $expTimeStr);
        $tra->addChild('service', $service);

        $traPath = LOG_DIR . "TRA_$service.xml";
        $tra->asXML($traPath);
        file_put_contents(LOG_DIR . "tra_$service.log", "TRA generado para $service:\n" . file_get_contents($traPath));
        return file_get_contents($traPath);
    }

    private function signTRA($tra) {
        $tmpTRA = LOG_DIR . 'TRA.tmp';
        $tmpCMS = LOG_DIR . 'TRA.cms';
        file_put_contents($tmpTRA, $tra);

        $status = openssl_pkcs7_sign(
            $tmpTRA,
            $tmpCMS,
            'file://' . $this->cert,
            ['file://' . $this->key, ''],
            [],
            !PKCS7_DETACHED
        );

        if ($status === false) {
            $error = openssl_error_string();
            file_put_contents(LOG_DIR . 'openssl_error.log', "Error al firmar con openssl_pkcs7_sign: $error");
            throw new \Exception("Error al firmar el TRA. Revisa logs/openssl_error.log");
        }

        $cms = '';
        $file = fopen($tmpCMS, 'r');
        $i = 0;
        while (!feof($file)) {
            $buffer = fgets($file);
            if ($i++ >= 4) {
                $cms .= $buffer;
            }
        }
        fclose($file);

        file_put_contents(LOG_DIR . 'cms.txt', $cms);
        file_put_contents(LOG_DIR . 'cms_raw.log', "CMS crudo generado:\n" . file_get_contents($tmpCMS));
        unlink($tmpTRA);
        unlink($tmpCMS);
        return $cms;
    }

    private function callWSAA($cms) {
        $soapRequest = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<soap:Body>' .
            '<loginCms xmlns="http://tempuri.org/">' .
            '<in0>' . htmlspecialchars($cms) . '</in0>' .
            '</loginCms>' .
            '</soap:Body>' .
            '</soap:Envelope>';

        file_put_contents(LOG_DIR . 'soap_request.log', "Solicitud SOAP enviada:\n" . $soapRequest);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/xml; charset=utf-8\r\nSOAPAction: \"\"\r\n",
                'content' => $soapRequest,
                'timeout' => 30
            ]
        ]);

        $response = @file_get_contents($this->wsaaUrl, false, $context);
        if ($response === false) {
            $error = error_get_last();
            $httpHeaders = implode("\n", $http_response_header ?? []);
            file_put_contents(LOG_DIR . 'wsaa_error.log', "Fallo al conectar con WSAA: " . ($error['message'] ?? 'Error desconocido') . "\nHeaders:\n" . $httpHeaders);
            throw new \Exception("Fallo al conectar con WSAA: " . ($error['message'] ?? 'Error desconocido') . ". Revisa logs/wsaa_error.log");
        }

        file_put_contents(LOG_DIR . 'wsaa_response.log', "Respuesta del WSAA:\nHeaders:\n" . implode("\n", $http_response_header ?? []) . "\nBody:\n" . $response);
        return $response;
    }

    private function initSoapClients() {
        $this->client = new \SoapClient($this->wswUrl . "?WSDL", [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1
        ]);
        $this->padronClient = new \SoapClient($this->padronUrl, [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1
        ]);
    }

    public function consultarCUIT($cuit) {
        if (!isset($this->credentials['ws_sr_padron_a13'])) {
            $this->login('ws_sr_padron_a13');
        }

        $request = [
            'token' => $this->credentials['ws_sr_padron_a13']['token'],
            'sign' => $this->credentials['ws_sr_padron_a13']['sign'],
            'cuitRepresentada' => $this->cuit,
            'idPersona' => $cuit
        ];

        file_put_contents(LOG_DIR . 'a13_request.log', "Solicitud a ws_sr_padron_a13 para CUIT $cuit:\n" . json_encode($request, JSON_PRETTY_PRINT));
        try {
            $result = $this->padronClient->getPersona($request);
            file_put_contents(LOG_DIR . 'a13_response.log', "Respuesta de ws_sr_padron_a13 para CUIT $cuit:\n" . json_encode($result, JSON_PRETTY_PRINT));
            
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
                    'impIVA' => 'No disponible'
                ];
            } else {
                $errorMsg = "CUIT $cuit no encontrado o sin datos de persona en la respuesta";
                file_put_contents(LOG_DIR . 'a13_error.log', "$errorMsg\nRespuesta completa:\n" . json_encode($result, JSON_PRETTY_PRINT));
                throw new \Exception($errorMsg);
            }
        } catch (\SoapFault $e) {
            file_put_contents(LOG_DIR . 'a13_error.log', "Error en ws_sr_padron_a13 para CUIT $cuit: " . $e->getMessage() . "\nSolicitud SOAP:\n" . $this->padronClient->__getLastRequest() . "\nRespuesta SOAP:\n" . $this->padronClient->__getLastResponse());
            throw new \Exception("Error en ws_sr_padron_a13 para CUIT $cuit: " . $e->getMessage() . ". Revisa logs/a13_error.log");
        }
    }

    public function crearFactura($data) {
        if (!isset($this->credentials['wsfe'])) {
            $this->login('wsfe');
        }
    
        // 1) Obtener el último comprobante autorizado
        $ultimoNro = $this->getLastCMP($data['PtoVta'], $data['TipoComp']);
    
        // 2) Convertir y validar fechas
        $timezone = new \DateTimeZone('America/Argentina/Buenos_Aires');
        $fechaComp = \DateTime::createFromFormat('d/m/Y', $data['FechaComp'], $timezone);
        if ($fechaComp === false) {
            throw new \Exception("Error: Formato de fecha inválido para FechaComp ({$data['FechaComp']}). Debe ser dd/mm/yyyy.");
        }
    
        $hoy = new \DateTime('now', $timezone);
        $limiteInferior = (clone $hoy)->modify('-10 days');
        $limiteSuperior = (clone $hoy)->modify('+10 days');
        if ($fechaComp < $limiteInferior || $fechaComp > $limiteSuperior) {
            throw new \Exception(
                "Error: La fecha de comprobante debe estar entre "
                . $limiteInferior->format('Y-m-d') . " y "
                . $limiteSuperior->format('Y-m-d')
            );
        }
    
        $fechaInicio = \DateTime::createFromFormat('d/m/Y', $data['facPeriodo_inicio'], $timezone);
        $fechaFin = \DateTime::createFromFormat('d/m/Y', $data['facPeriodo_fin'], $timezone);
        if ($fechaInicio === false || $fechaFin === false) {
            throw new \Exception("Error: Formato de fechas de servicio inválido. Deben ser dd/mm/yyyy.");
        }
        if ($fechaInicio > $fechaFin) {
            throw new \Exception("Error: La fecha de inicio del servicio ({$data['facPeriodo_inicio']}) no puede ser posterior a la de fin ({$data['facPeriodo_fin']}).");
        }
    
        $fechaVto = \DateTime::createFromFormat('d/m/Y', $data['fechaUltimoDia'], $timezone);
        if ($fechaVto === false) {
            throw new \Exception("Error: Formato de fecha de vencimiento inválido ({$data['fechaUltimoDia']}). Debe ser dd/mm/yyyy.");
        }
    
        // 3) Cálculo de montos: neto, IVA 21%, total
        $importeNeto = (float)$data['facTotal'];
        $importeIva = round($importeNeto * 0.21, 2);
        $importeTotal = $importeNeto + $importeIva;
    
        // 4) Construir request
        $request = [
            'Auth' => [
                'Token' => $this->credentials['wsfe']['token'],
                'Sign' => $this->credentials['wsfe']['sign'],
                'Cuit' => $this->cuit
            ],
            'FeCAEReq' => [
                'FeCabReq' => [
                    'CantReg' => 1,
                    'PtoVta' => (int)$data['PtoVta'], // Asegurar que sea entero
                    'CbteTipo' => (int)$data['TipoComp']
                ],
                'FeDetReq' => [
                    'FECAEDetRequest' => [
                        'Concepto' => 2, // Servicios
                        'DocTipo' => 80, // CUIT
                        'DocNro' => (int)$data['facCuit'], // Asegurar que sea entero
                        'CbteDesde' => $ultimoNro + 1,
                        'CbteHasta' => $ultimoNro + 1,
                        'CbteFch' => $fechaComp->format('Ymd'),
                        'ImpNeto' => $importeNeto,
                        'ImpIVA' => $importeIva,
                        'ImpTotal' => $importeTotal,
                        'ImpOpEx' => 0,
                        'ImpTotConc' => 0,
                        'ImpTrib' => 0,
                        'Iva' => [
                            'AlicIva' => [
                                'Id' => 5, // 21%
                                'BaseImp' => $importeNeto,
                                'Importe' => $importeIva
                            ]
                        ],
                        'FchServDesde' => $fechaInicio->format('Ymd'),
                        'FchServHasta' => $fechaFin->format('Ymd'),
                        'FchVtoPago' => $fechaVto->format('Ymd'),
                        'MonId' => 'PES',
                        'MonCotiz' => 1
                    ]
                ]
            ]
        ];
    
        // 5) Llamada a AFIP
        file_put_contents(LOG_DIR . 'wsfe_request.log', "Solicitud a FECAESolicitar:\n" . json_encode($request, JSON_PRETTY_PRINT));
        try {
            $response = $this->client->FECAESolicitar($request);
            file_put_contents(LOG_DIR . 'wsfe_response.log', "Respuesta de FECAESolicitar:\n" . json_encode($response, JSON_PRETTY_PRINT));
            
            if (isset($response->FECAESolicitarResult->FeCabResp->Resultado)) {
                if ($response->FECAESolicitarResult->FeCabResp->Resultado == 'A') {
                    $cae = $response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE;
                    $vtoCae = $response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAEFchVto;
                    return ['CAE' => $cae, 'Vencimiento' => $vtoCae];
                } else {
                    $observaciones = isset($response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones)
                        ? json_encode($response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones)
                        : 'No observaciones disponibles';
                    throw new \Exception("Error al autorizar factura: $observaciones");
                }
            } else {
                throw new \Exception("Estructura de respuesta inválida de FECAESolicitar: " . json_encode($response));
            }
        } catch (\SoapFault $e) {
            file_put_contents(
                LOG_DIR . 'wsfe_error.log',
                "Error en FECAESolicitar: " . $e->getMessage()
                . "\nSolicitud SOAP:\n" . $this->client->__getLastRequest()
                . "\nRespuesta SOAP:\n" . $this->client->__getLastResponse()
            );
            throw new \Exception("Error en wsfe: " . $e->getMessage() . ". Revisa logs/wsfe_error.log");
        }
    }

    public function getLastCMP($ptoVta, $tipoComp) {
        if (!isset($this->credentials['wsfe'])) {
            $this->login('wsfe');
        }

        $request = [
            'Auth' => [
                'Token' => $this->credentials['wsfe']['token'],
                'Sign' => $this->credentials['wsfe']['sign'],
                'Cuit' => $this->cuit
            ],
            'PtoVta' => $ptoVta,
            'CbteTipo' => $tipoComp
        ];
        $response = $this->client->FECompUltimoAutorizado($request);
        return $response->FECompUltimoAutorizadoResult->CbteNro;
    }
}