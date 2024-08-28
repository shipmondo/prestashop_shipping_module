<?php

namespace Shipmondo;

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
     * @param string $frontendKey
     */
    public function __construct($frontendKey)
    {
        $this->frontendKey = $frontendKey;
        $this->client = new \GuzzleHttp\Client();
        $this->baseUrl = 'https://service-points.shipmondo.com/';
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
        try {
            $response = $this->client->request($method, $fullUrl, [
                'headers' => [
                    'User-Agent' => 'Shipmondo Prestashop Module v',
                ],
                'query' => array_merge($query, ['frontend_key' => $this->frontendKey]),
            ]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [];
        }

        return json_decode($response->getBody()->getContents());
    }
}