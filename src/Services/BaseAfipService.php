<?php

namespace AfipApi\Services;

use AfipApi\Core\Client;
use SoapClient;
use SoapFault;
use Exception;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;

abstract class BaseAfipService
{
    protected Client $client;
    protected array $credentials = [];
    protected ?SoapClient $soapClient = null;
    protected string $logPrefix;

    // URLs AFIP
    protected const URLS = [
        'prod' => [
            'wsaa' => 'https://wsaa.afip.gov.ar/ws/services/LoginCms',
            'padron' => 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA13?WSDL'
        ],
        'homo' => [
            'wsaa' => 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms',
            'padron' => 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA13?WSDL'
        ]
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->logPrefix = strtolower(static::class);
    }

    abstract protected function getServiceUrl(): string;
    abstract protected function getServiceName(): string;

    protected function login(): void
    {
        $serviceName = $this->getServiceName();
        
        if (isset($this->credentials[$serviceName]) && $this->isCredentialValid($serviceName)) {
            return;
        }

        $tra = $this->createTRA($serviceName);
        $cms = $this->signTRA($tra);
        $response = $this->callWSAA($cms);
        $this->parseWSAAResponse($response, $serviceName);

        $this->log("Login exitoso para $serviceName");
    }

    private function createTRA(string $service): string
    {
        $timezone = new DateTimeZone('America/Argentina/Buenos_Aires');
        $now = new DateTime('now', $timezone);

        $genTime = clone $now;
        $genTime->modify('-10 minutes');
        $expTime = clone $now;
        $expTime->modify('+10 minutes');

        $genTime->setTimezone(new DateTimeZone('UTC'));
        $expTime->setTimezone(new DateTimeZone('UTC'));

        $uniqueId = $now->format('U');
        $genTimeStr = $genTime->format('c');
        $expTimeStr = $expTime->format('c');

        $tra = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><loginTicketRequest version="1.0"></loginTicketRequest>');
        $tra->addChild('header');
        $tra->header->addChild('uniqueId', $uniqueId);
        $tra->header->addChild('generationTime', $genTimeStr);
        $tra->header->addChild('expirationTime', $expTimeStr);
        $tra->addChild('service', $service);

        return $tra->asXML();
    }

    private function signTRA(string $tra): string
    {
        $tmpTRA = sys_get_temp_dir() . '/TRA_' . uniqid() . '.tmp';
        $tmpCMS = sys_get_temp_dir() . '/TRA_' . uniqid() . '.cms';
        
        file_put_contents($tmpTRA, $tra);

        $status = openssl_pkcs7_sign(
            $tmpTRA,
            $tmpCMS,
            'file://' . $this->client->getCertificatePath(),
            ['file://' . $this->client->getPrivateKeyPath(), ''],
            [],
            !PKCS7_DETACHED
        );

        if ($status === false) {
            $error = openssl_error_string();
            $this->log("Error al firmar TRA: $error", 'error');
            throw new Exception("Error al firmar el TRA: $error");
        }

        $cms = '';
        $file = fopen($tmpCMS, 'r');
        if ($file === false) {
            throw new Exception("No se pudo abrir el archivo CMS temporal");
        }
        
        $i = 0;
        while (!feof($file)) {
            $buffer = fgets($file);
            if ($buffer === false) break;
            if ($i++ >= 4) {
                $cms .= $buffer;
            }
        }
        fclose($file);

        // Limpiar archivos temporales
        unlink($tmpTRA);
        unlink($tmpCMS);

        return $cms;
    }

    private function callWSAA(string $cms): string
    {
        $wsaaUrl = self::URLS[$this->client->getEnvironment()]['wsaa'];
        
        $soapRequest = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<soap:Body>' .
            '<loginCms xmlns="http://tempuri.org/">' .
            '<in0>' . htmlspecialchars($cms) . '</in0>' .
            '</loginCms>' .
            '</soap:Body>' .
            '</soap:Envelope>';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/xml; charset=utf-8\r\nSOAPAction: \"\"\r\n",
                'content' => $soapRequest,
                'timeout' => 30
            ]
        ]);

        $response = @file_get_contents($wsaaUrl, false, $context);
        if ($response === false) {
            $error = error_get_last();
            $this->log("Error al conectar con WSAA: " . ($error['message'] ?? 'Error desconocido'), 'error');
            throw new Exception("Error al conectar con WSAA: " . ($error['message'] ?? 'Error desconocido'));
        }

        return $response;
    }

    private function parseWSAAResponse(string $response, string $service): void
    {
        $xmlResponse = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xmlResponse === false) {
            $this->log("Error al parsear respuesta WSAA para $service", 'error');
            throw new Exception("Error al parsear la respuesta del WSAA para $service");
        }

        $namespaces = $xmlResponse->getNamespaces(true);
        $body = $xmlResponse->children($namespaces['soapenv'])->Body;

        if (isset($body->Fault)) {
            $faultString = (string)$body->Fault->faultstring;
            $this->log("WSAA Fault para $service: $faultString", 'error');
            throw new Exception("Error del WSAA para $service: $faultString");
        }

        $loginCmsResponse = $body->children()->loginCmsResponse;
        if (!$loginCmsResponse || empty($loginCmsResponse->loginCmsReturn)) {
            $this->log("loginCmsReturn no encontrado para $service", 'error');
            throw new Exception("loginCmsReturn no encontrado en respuesta WSAA para $service");
        }

        $loginCmsReturn = (string)$loginCmsResponse->loginCmsReturn;
        $loginTicketResponse = simplexml_load_string(html_entity_decode($loginCmsReturn));
        if ($loginTicketResponse === false) {
            $this->log("Error al parsear loginCmsReturn para $service", 'error');
            throw new Exception("Error al parsear loginCmsReturn para $service");
        }

        $this->credentials[$service] = [
            'token' => (string)$loginTicketResponse->credentials->token,
            'sign' => (string)$loginTicketResponse->credentials->sign,
            'expires' => strtotime((string)$loginTicketResponse->header->expirationTime)
        ];
    }

    private function isCredentialValid(string $service): bool
    {
        if (!isset($this->credentials[$service])) {
            return false;
        }

        // Verificar si expira en los próximos 5 minutos
        $expirationTime = $this->credentials[$service]['expires'] ?? 0;
        return time() < ($expirationTime - 300);
    }

    protected function initSoapClient(): void
    {
        if ($this->soapClient !== null) {
            return;
        }

        try {
            $this->soapClient = new SoapClient($this->getServiceUrl() . '?WSDL', [
                'soap_version' => SOAP_1_1,
                'exceptions' => true,
                'trace' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE
            ]);
        } catch (SoapFault $e) {
            $this->log("Error al inicializar SOAP client: " . $e->getMessage(), 'error');
            throw new Exception("Error al inicializar cliente SOAP: " . $e->getMessage());
        }
    }

    protected function callSoapMethod(string $method, array $parameters): mixed
    {
        $this->login();
        $this->initSoapClient();

        try {
            $this->log("Llamando método $method", 'debug');
            $result = $this->soapClient->$method($parameters);
            $this->log("Método $method ejecutado exitosamente", 'debug');
            return $result;
        } catch (SoapFault $e) {
            $this->log("Error en método $method: " . $e->getMessage(), 'error');
            $this->log("Request: " . $this->soapClient->__getLastRequest(), 'debug');
            $this->log("Response: " . $this->soapClient->__getLastResponse(), 'debug');
            throw new Exception("Error en $method: " . $e->getMessage());
        }
    }

    protected function getAuthArray(): array
    {
        $serviceName = $this->getServiceName();
        return [
            'Token' => $this->credentials[$serviceName]['token'],
            'Sign' => $this->credentials[$serviceName]['sign'],
            'Cuit' => $this->client->getCuit()
        ];
    }

    protected function log(string $message, string $level = 'info'): void
    {
        $logFile = __DIR__ . "/../../logs/{$this->logPrefix}_{$this->client->getCuit()}_{$level}.log";
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    protected function validateDateFormat(string $date, string $format = 'd/m/Y'): DateTime
    {
        $timezone = new DateTimeZone('America/Argentina/Buenos_Aires');
        $dateTime = DateTime::createFromFormat($format, $date, $timezone);
        
        if ($dateTime === false) {
            throw new Exception("Formato de fecha inválido: $date. Esperado: $format");
        }

        return $dateTime;
    }

    protected function validateDateRange(DateTime $date, int $daysBefore = 10, int $daysAfter = 10): void
    {
        $timezone = new DateTimeZone('America/Argentina/Buenos_Aires');
        $now = new DateTime('now', $timezone);
        $minDate = (clone $now)->modify("-$daysBefore days");
        $maxDate = (clone $now)->modify("+$daysAfter days");

        if ($date < $minDate || $date > $maxDate) {
            throw new Exception(
                "La fecha debe estar entre {$minDate->format('Y-m-d')} y {$maxDate->format('Y-m-d')}"
            );
        }
    }
} 