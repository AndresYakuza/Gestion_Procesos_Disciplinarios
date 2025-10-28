<?php
namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;

class SorttimeClient
{
    private string $baseUri;
    private CURLRequest $http;

    public function __construct(?string $baseUri = null)
    {
        $this->baseUri = $baseUri ?: (env('sorttime.baseURI') ?: 'https://sorttime.co/SorttimeWS/');
        $this->http = \Config\Services::curlrequest([
            'baseURI'         => $this->baseUri,
            'timeout'         => 300,  
            'connect_timeout' => 30,
            'http_errors'     => false,
            'curl'            => [
                CURLOPT_TIMEOUT        => 300,  
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_LOW_SPEED_TIME => 60,   
                CURLOPT_LOW_SPEED_LIMIT=> 1024,
            ],
        ]);
    }

    public function getToken(string $nit): string
    {
        $res = $this->http->get('api/GenerateToken/' . rawurlencode($nit), [
            'headers' => ['Accept' => 'application/json'],
        ]);

        if ($res->getStatusCode() !== 200) {
            throw new \RuntimeException('No se pudo obtener token (HTTP ' . $res->getStatusCode() . '). ' . $res->getBody());
        }

        $data = json_decode($res->getBody(), true);
        if (!is_array($data) || empty($data['token'])) {
            throw new \RuntimeException('Respuesta de token inválida.');
        }
        return $data['token'];
    }

    public function getMasterWorkers(string $nit, string $desdeDdMmYyyy, string $hastaDdMmYyyy): array
    {
        $token = $this->getToken($nit);

        $res = $this->http->post('reports/masterWorkers/generate', [
            'auth'    => [$nit, $token], // Basic Auth
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'json'    => ['fechaDesde' => $desdeDdMmYyyy, 'fechaHasta' => $hastaDdMmYyyy],
        ]);

        if ($res->getStatusCode() !== 200) {
            throw new \RuntimeException('Error generando informe (HTTP ' . $res->getStatusCode() . '). ' . $res->getBody());
        }

        $rows = json_decode($res->getBody(), true);
        if (!is_array($rows)) {
            throw new \RuntimeException('Respuesta inesperada del informe (no es arreglo JSON).');
        }
        return $rows;
    }
}
