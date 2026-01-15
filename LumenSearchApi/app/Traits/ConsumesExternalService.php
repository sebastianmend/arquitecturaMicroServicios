<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ConsumesExternalService
{
    /**
     * Send a request to any service
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $requestUrl The endpoint URL
     * @param array $formParams Form parameters for POST/PUT requests
     * @param array $headers Additional headers
     * @return array|string Decoded JSON response or raw body
     */
    public function performRequest($method, $requestUrl, $formParams = [], $headers = [])
    {
        $client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 10.0,
            'http_errors' => true,
        ]);

        if (isset($this->secret)) {
            $headers['Authorization'] = $this->secret;
        }

        $options = ['headers' => $headers];

        if (!empty($formParams)) {
            if (in_array(strtoupper($method), ['GET', 'DELETE'])) {
                $options['query'] = $formParams;
            } else {
                $options['form_params'] = $formParams;
            }
        }

        try {
            $response = $client->request($method, $requestUrl, $options);
            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($decoded['data']) && is_array($decoded) && count($decoded) === 1) {
                    return $decoded['data'];
                }
                return $decoded;
            }
            return $body;

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new \RuntimeException('Connection failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
