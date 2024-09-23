<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo;

use Module;
use Shipmondo\Exception\ShipmondoApiException;

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
    public function getCarriers(): array
    {
        return $this->request('GET', 'carriers.json');
    }

    /**
     * @param array $query
     * @return array
     */
    public function getServicePoints($query): array
    {
        return $this->request('GET', 'pickup-points.json', $query);
    }

    /**
     * @param string $mtethod
     * @param string $url
     * @param array $query
     * @return array
     */
    private function request($method, $url, $query = []): array
    {
        $fullUrl = $this->baseUrl . $url;
        try {
            $response = $this->client->request($method, $fullUrl, [
                'headers' => [
                    'User-Agent' => 'Shipmondo Prestashop Module v' . $this->module->version,
                ],
                'query' => array_merge($query, ['frontend_key' => $this->frontendKey]),
            ]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $error_message = $response_body = $e->getResponse()->getBody()->getContents();
            $response_body = json_decode($response_body);
            if (isset($response_body->message)) {
                $error_message = $response_body->message;
            }
            throw new ShipmondoApiException($error_message);
        }

        return json_decode($response->getBody()->getContents());
    }
}