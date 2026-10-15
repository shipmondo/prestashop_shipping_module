<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo;

use Shipmondo\Exception\ShipmondoApiException;
use Symfony\Contracts\HttpClient\ResponseInterface;

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

    /**
     * @var string
     */
    private $baseUri;

    private const API_V3_URI = 'https://app.shipmondo.com/api/public/v3/';

    /**
     * Environment variable that overrides the API base URL, e.g. to point at a mock server in end-to-end tests.
     * Shops do not set it, so they always use API_V3_URI.
     */
    public const API_URL_ENV_VAR = 'SHIPMONDO_API_URL';

    public function __construct(\Module $module, string $frontendKey, \Symfony\Component\HttpClient\HttpClient $client)
    {
        $this->module = $module;
        $this->frontendKey = $frontendKey;
        $this->client = $client->create();
        $this->baseUri = self::resolveBaseUri();
    }

    /**
     * Get the API base URL, always ending with a slash.
     */
    public static function resolveBaseUri(): string
    {
        $override = getenv(self::API_URL_ENV_VAR);

        if (!is_string($override) || trim($override) === '') {
            return self::API_V3_URI;
        }

        return rtrim(trim($override), '/') . '/';
    }

    public function getCarriers(): array
    {
        return $this->request('GET', $this->baseUri . 'shipping_modules/carriers');
    }

    public function getCarrierProducts(string $carrierCode): array
    {
        return $this->request('GET', $this->baseUri . 'shipping_modules/products', ['carrier_code' => $carrierCode]);
    }

    /**
     * Get products for several carriers. The requests run concurrently.
     * Carriers whose request fails are left out of the result.
     *
     * @param string[] $carrierCodes
     *
     * @return array<string, array> Products keyed by carrier code
     */
    public function getCarrierProductsForCarriers(array $carrierCodes): array
    {
        $responses = [];

        foreach ($carrierCodes as $carrierCode) {
            $responses[$carrierCode] = $this->sendRequest('GET', $this->baseUri . 'shipping_modules/products', ['carrier_code' => $carrierCode]);
        }

        $carrierProducts = [];

        foreach ($responses as $carrierCode => $response) {
            try {
                $carrierProducts[$carrierCode] = $this->decodeResponse($response);
            } catch (ShipmondoApiException $e) {
                continue;
            }
        }

        return $carrierProducts;
    }

    public function getCarrierProductServicePointTypes(string $productCode): array
    {
        return $this->request('GET', $this->baseUri . 'service_point/service_point_types', [
            'product_code' => $productCode,
        ]);
    }

    public function getServicePoints(string $productCode, ?array $servicePointTypes, string $countryCode, string $zipcode, string $city, string $address): array
    {
        $url = $this->baseUri . 'service_point/service_points';

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
        return $this->decodeResponse($this->sendRequest($method, $url, $query));
    }

    /**
     * Start a request. The response is lazy, so several requests can run concurrently before their content is read.
     */
    private function sendRequest(string $method, string $url, array $query = []): ResponseInterface
    {
        return $this->client->request($method, $url, [
            'headers' => [
                'User-Agent' => 'Shipmondo Prestashop Module v' . $this->module->version,
            ],
            'query' => array_merge($query, [
                'request_url' => _PS_BASE_URL_,
                'request_version' => _PS_VERSION_,
                'module_version' => $this->module->version,
                'shipping_module_type' => 'prestashop',
                'frontend_key' => $this->frontendKey,
                'php_version' => PHP_VERSION ?? '',
            ]),
        ]);
    }

    /**
     * @throws ShipmondoApiException
     */
    private function decodeResponse(ResponseInterface $response): array
    {
        try {
            $response_body = $response->getContent();

            return json_decode($response_body);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $error_message = $e->getResponse()->getContent(false);

            $response_body = json_decode($error_message);

            if (isset($response_body->message)) {
                $error_message = $response_body->message;
            }

            throw new ShipmondoApiException($error_message);
        }
    }
}
