<?php

namespace Shipmondo;

use Module;

class ApiClient
{
    /**
     * @var string
     */
    private $frontendKey;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var Module
     */
    private $module;

    /**
     * @param string $frontendKey
     * @param \GuzzleHttp\Client $client
     * @param string $baseUrl
     * @param Module $module
     */
    public function __construct(Module $module, string $frontendKey, \GuzzleHttp\Client $client, string $baseUrl)
    {
        $this->module = $module;
        $this->frontendKey = $frontendKey;
        $this->client = $client;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return array
     */
    public function getCarriers()
    {
        return $this->request('GET', 'carriers.json');
    }

    /**
     * @param array $query
     * @return array
     */
    public function getServicePoints($query)
    {
        return $this->request('GET', 'pickup-points.json', $query);
    }

    /**
     * @param string $mtethod
     * @param string $url
     * @param array $query
     */
    private function request($method, $url, $query = [])
    {
        $fullUrl = $this->baseUrl . $url;
        //try {
            $response = $this->client->request($method, $fullUrl, [
                'headers' => [
                    'User-Agent' => 'Shipmondo Prestashop Module v' . $this->module->version,
                ],
                'query' => array_merge($query, ['frontend_key' => $this->frontendKey]),
            ]);
        /*} catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [];
        }*/

        return json_decode($response->getBody()->getContents());
    }
}