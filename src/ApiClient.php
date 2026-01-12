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
     * @var \Module
     */
    private $module;

    private const API_V3_URI = 'https://app.shipmondo.com/api/public/v3/';

    public function __construct(\Module $module, string $frontendKey, \Symfony\Component\HttpClient\HttpClient $client)
    {
        $this->module = $module;
        $this->frontendKey = $frontendKey;
        $this->client = $client->create();
    }

    public function getCarriers(): array
    {
        return $this->request('GET', self::API_V3_URI . 'shipping_modules/carriers');
    }

    public function getCarrierProducts(string $carrierCode): array
    {
        return $this->request('GET', self::API_V3_URI . 'shipping_modules/products', ['carrier_code' => $carrierCode]);
    }

    public function getCarrierProductServicePointTypes(string $productCode): array
    {
        return $this->request('GET', self::API_V3_URI . 'service_point/service_point_types', [
            'product_code' => $productCode,
        ]);
    }

    public function getServicePoints(string $productCode, ?array $servicePointTypes, string $countryCode, string $zipcode, string $city, string $address): array
    {
        $url = self::API_V3_URI . 'service_point/service_points';

        if (is_array($servicePointTypes) && count($servicePointTypes) > 0) {
            $url .= '?service_point_types[]=' . implode('&service_point_types[]=', $servicePointTypes);
        }

        return $this->request('GET', $url, [
            'quantity' => 10,
            'product_code' => $productCode,
            'country_code' => trim($countryCode),
            'zipcode' => trim($zipcode),
            'city' => trim($city),
            'address' => trim($address),
        ]);
    }

    /**
     * @throws ShipmondoApiException
     */
    private function request(string $method, string $url, array $query = []): array
    {
        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'User-Agent' => 'Shipmondo Prestashop Module v' . $this->module->version,
                ],
                'query' => array_merge($query, [
                    'request_url' => _PS_BASE_URL_,
                    'request_version' => _PS_VERSION_,
                    'module_version' => $this->module->version,
                    'shipping_module_type' => 'prestashop',
                    'frontend_key' => $this->frontendKey,
                ]),
            ]);

            $response_body = $response->getContent();

            return json_decode($response_body);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $response_body = $e->getResponse()->getContent(false);
            $error_message = $response_body;

            $response_body = json_decode($response_body);

            if (isset($response_body->message)) {
                $error_message = $response_body->message;
            }

            throw new ShipmondoApiException($error_message);
        }
    }
}
