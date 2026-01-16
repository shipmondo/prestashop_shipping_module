<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Shipmondo\Exception\ShipmondoApiException;

class ShipmondoCarrierHandler
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var array
     */
    private $carriers;

    public function __construct(ConfigurationInterface $configuration, ApiClient $apiClient)
    {
        $this->configuration = $configuration;
        $this->apiClient = $apiClient;
    }

    /**
     * Get available carriers from Shipmondo
     */
    public function getCarriers(): array
    {
        if (!$this->carriers) {
            $this->carriers = $this->fetchCarriers();
        }

        return $this->carriers;
    }

    /**
     * Get carrier by code
     */
    public function getCarrier(string $carrierCode): ?object
    {
        $carriers = self::getCarriers();

        foreach ($carriers as $carrier) {
            if ($carrier->code === $carrierCode) {
                return $carrier;
            }
        }

        return null;
    }

    /**
     * Get products for a carrier
     */
    public function getProducts(string $carrierCode): array
    {
        $carriers = self::getCarriers();

        foreach ($carriers as $carrier) {
            if ($carrier->code === $carrierCode) {
                return $carrier->products;
            }
        }

        return [];
    }

    /**
     * Get carrier name. Fallback to carrier code if name is not found.
     */
    public function getCarrierName(string $carrierCode): string
    {
        $carrier = self::getCarrier($carrierCode);

        return $carrier ? $carrier->name : $carrierCode;
    }

    /**
     * Get product name.
     */
    public function getProductName(string $productCode): string
    {
        return ucwords(str_replace('_', ' ', $productCode));
    }

    public function getCarrierProducts(string $carrierCode): array
    {
        $cacheKey = 'SHIPMONDO_CARRIER_PRODUCTS_CACHE_' . $carrierCode;
        $expirationTimeCacheKey = $cacheKey . '_EXPIRATION';

        $expirationTime = (int) $this->configuration->get($expirationTimeCacheKey);

        if ($expirationTime && $expirationTime > time()) {
            $carrierProducts = $this->configuration->get($cacheKey);

            if ($carrierProducts) {
                return json_decode($carrierProducts, false);
            }
        }

        $value = $this->apiClient->getCarrierProducts($carrierCode);

        $this->configuration->set($cacheKey, json_encode($value));
        $this->configuration->set($expirationTimeCacheKey, time() + 900);

        return $value;
    }

    private function carrierHasServicePointProducts(string $carrierCode): bool
    {
        try {
            $carrierProducts = self::getCarrierProducts($carrierCode);

            foreach ($carrierProducts as $carrierProduct) {
                if (isset($carrierProduct->service_point_product) && $carrierProduct->service_point_product === true) {
                    return true;
                }
            }

            return false;
        } catch (ShipmondoApiException $e) {
            return false;
        }
    }

    /**
     * Fetch carriers from Shipmondo API if cache is not valid
     */
    private function fetchCarriers(): array
    {
        $availableCarriers = $this->configuration->get('SHIPMONDO_AVAILABLE_CARRIERS');
        $expirationTime = (int) $this->configuration->get('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION');

        if (!$availableCarriers || !$expirationTime || $expirationTime < time()) {
            $availableCarriers = $this->apiClient->getCarriers();

            // Change boolean values to array of products to prepare for the future
            foreach ($availableCarriers as $availableCarrier) {
                $products = [];

                $availableCarrier->products = [
                    'private' => true,
                    'business' => true,
                    'service_point' => self::carrierHasServicePointProducts($availableCarrier->code),
                ];

                foreach ($availableCarrier->products as $productCode => $hasProduct) {
                    if ($hasProduct) {
                        $product = new \stdClass();
                        $product->name = ucwords(str_replace('_', ' ', $productCode));
                        $product->code = $productCode;
                        $products[] = $product;
                    }
                }
                $availableCarrier->products = $products;
            }

            $this->configuration->set('SHIPMONDO_AVAILABLE_CARRIERS', json_encode($availableCarriers));
            $this->configuration->set('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION', time() + 21600); // 6 hours

            return $availableCarriers;
        }

        return json_decode($availableCarriers);
    }

    public function getServicePointTypes(string $productCode): array
    {
        $cacheKey = 'SHIPMONDO_SERVICE_POINT_TYPES_CACHE_' . $productCode;
        $expirationTimeCacheKey = $cacheKey . '_EXPIRATION';

        $expirationTime = (int) $this->configuration->get($expirationTimeCacheKey);

        if ($expirationTime && $expirationTime > time()) {
            $servicePointTypes = $this->configuration->get($cacheKey);

            if ($servicePointTypes) {
                return json_decode($servicePointTypes, false);
            }
        }

        $value = $this->apiClient->getCarrierProductServicePointTypes($productCode);

        $this->configuration->set($cacheKey, json_encode($value));
        $this->configuration->set($expirationTimeCacheKey, time() + 900);

        return $value;
    }

    public function getCarrierFormValues(?string $carrierCode, ?string $productCode, ?string $carrierProductCode, ?array $servicePointTypes): array
    {
        if (!$carrierCode) {
            $productCode = null;
        }

        if (!$productCode) {
            $productCode = null;
        }

        if (!$carrierProductCode) {
            $carrierProductCode = null;
        }

        if (!$servicePointTypes) {
            $servicePointTypes = null;
        }

        $carrierChoices = [];

        $allCarriers = $this->getCarriers();
        foreach ($allCarriers as $carrier) {
            if (!$carrierCode) {
                $carrierCode = $carrier->code;
            }

            $carrierChoices[$carrier->name] = $carrier->code;
        }

        $productCodeChoices = [];

        $allProductTypes = is_string($carrierCode) ? $this->getProducts($carrierCode) : [];
        $validProductCode = false;
        foreach ($allProductTypes as $product) {
            if (!$productCode) {
                $productCode = $product->code;
            }

            $productCodeChoices[$product->name] = $product->code;

            if ($productCode === $product->code) {
                $validProductCode = true;
            }
        }

        if (!$validProductCode) {
            $productCode = 'private';
            $servicePointTypes = null;
        }

        $isServicePointDelivery = $productCode === 'service_point';

        $allCarrierProducts = $isServicePointDelivery ? $this->getCarrierProducts($carrierCode) : [];

        $applicableCarrierProductChoices = [];

        $validCarrierProductCode = false;
        $receiverCountries = null;
        foreach ($allCarrierProducts as $product) {
            if (!isset($product->service_point_product) || $product->service_point_product !== true) {
                continue;
            }

            if (!$carrierProductCode) {
                $carrierProductCode = $product->product_code;
                $validCarrierProductCode = true;
            }

            $applicableCarrierProductChoices[$product->name] = $product->product_code;

            if ($carrierProductCode === $product->product_code) {
                $validCarrierProductCode = true;
                $receiverCountries = $product->receiver_countries ?? null;
            }
        }

        if (!$validCarrierProductCode) {
            $carrierProductCode = null;

            foreach ($allCarrierProducts as $product) {
                if (!isset($product->service_point_product) || $product->service_point_product !== true) {
                    continue;
                }

                $carrierProductCode = $product->product_code;
                $receiverCountries = $product->receiver_countries ?? null;
                break;
            }
        }

        if (!is_array($servicePointTypes) && is_string($carrierProductCode)) {
            $servicePointTypes = [];
        }

        $servicePointTypesChoices = [];

        $allServicePointTypes = $isServicePointDelivery && is_string($carrierProductCode)
            ? $this->getServicePointTypes($carrierProductCode)
            : [];
        foreach ($allServicePointTypes as $servicePointType) {
            $servicePointTypesChoices[$servicePointType->name] = $servicePointType->code;
        }

        return [
            'choices' => [
                'carrier_code' => $carrierChoices,
                'product_code' => $productCodeChoices,
                'carrier_product_code' => $applicableCarrierProductChoices,
                'service_point_types' => $servicePointTypesChoices,
            ],
            'default' => [
                'carrier_code' => $carrierCode,
                'product_code' => $productCode,
                'carrier_product_code' => $carrierProductCode,
                'service_point_types' => $servicePointTypes,
            ],
            'receiver_countries' => $receiverCountries,
        ];
    }
}
