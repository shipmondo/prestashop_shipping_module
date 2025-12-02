<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo;

use Shipmondo\Exception\ShipmondoApiException;

class ApiClient
{
    /**
     * @var string
     */
    private $frontendKey;

    /**
     * @var \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @param string $frontendKey
     * @param \Symfony\Component\HttpClient\HttpClient $client
     * @param string $baseUrl
     * @param \Module $module
     */
    public function __construct(\Module $module, string $frontendKey, \Symfony\Component\HttpClient\HttpClient $client, string $baseUrl)
    {
        $this->module = $module;
        $this->frontendKey = $frontendKey;
        $this->client = $client->create();
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
     *
     * @return array
     */
    public function getServicePoints($query): array
    {
        $servicePoints = $this->request('GET', 'pickup-points.json', $query);

        // Overide carrier code to ensure it is the same as requested
        foreach ($servicePoints as $key => $servicePoint) {
            $servicePoint->carrier_code = $query['carrier_code'];
        }

        return $servicePoints;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $query
     *
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

            $response_body = $response->getContent();

            return json_decode($response_body);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $error_message = $response_body = $e->getResponse()->getContent();

            $response_body = json_decode($response_body);

            if (isset($response_body->message)) {
                $error_message = $response_body->message;
            }

            throw new ShipmondoApiException($error_message);
        }
    }
}
