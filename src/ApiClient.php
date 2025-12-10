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

    public function __construct(\Module $module, string $frontendKey, \Symfony\Component\HttpClient\HttpClient $client)
    {
        $this->module = $module;
        $this->frontendKey = $frontendKey;
        $this->client = $client->create();
    }

    public function getCarriers(): array
    {
        return $this->request('GET', 'https://service-points.shipmondo.com/carriers.json');
    }

    public function getCarrierProducts(string $carrierCode, bool $ownAgreementOnly = false): array
    {
        $query = ['carrier_code' => $carrierCode, 'own_agreement_only' => $ownAgreementOnly];

        return $this->request('GET', 'https://app.shipmondo.com/api/public/v3/shipping_modules/products', $query);
    }

    public function getCarrierProductServicePointTypes(string $productCode, ?string $countryCode): array
    {
        $query = ['product_code' => $productCode];

        if ($countryCode !== null) {
            $query['country'] = $countryCode;
        }

        return $this->request('GET', 'https://app.shipmondo.com/api/public/v3/service_point/service_point_types', $query);
    }

    public function getServicePoints(array $query): array
    {
        $servicePoints = $this->request('GET', 'https://service-points.shipmondo.com/pickup-points.json', $query);

        // Overide carrier code to ensure it is the same as requested
        foreach ($servicePoints as $key => $servicePoint) {
            $servicePoint->carrier_code = $query['carrier_code'];
        }

        return $servicePoints;
    }

    // TODO: rename when we remove the legacy code
    public function getServicePointsUsingProductCode(string $product_code, array $service_point_types, string $country_code, string $zipcode, ?string $city, ?string $address, int $quantity = 10): array
    {
        $query = [
            'quantity' => $quantity,
            'product_code' => $product_code,
            'country_code' => rawurlencode(trim($country_code)),
            'zipcode' => rawurlencode(trim($zipcode)),
        ];

        if (is_array($service_point_types) && count($service_point_types) > 0) {
            $query['service_point_types[]'] = implode('&service_point_types[]=', $service_point_types);
        }

        if (is_string($city)) {
            $query['city'] = rawurlencode(trim($city));
        }

        if (is_string($address)) {
            $query['address'] = rawurlencode(trim($address));
        }

        return $this->request('GET', 'https://app.shipmondo.com/api/public/v3/service_point/service_points', $query);
    }

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
            $error_message = $response_body = $e->getResponse()->getContent();

            $response_body = json_decode($response_body);

            if (isset($response_body->message)) {
                $error_message = $response_body->message;
            }

            throw new ShipmondoApiException($error_message);
        }
    }
}
