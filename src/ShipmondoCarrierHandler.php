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

    /**
     * @var array
     */
    private $carrierProductCache;

    /**
     * @var array
     */
    private $servicePointTypeCache;

    public function __construct(ConfigurationInterface $configuration, ApiClient $apiClient)
    {
        $this->configuration = $configuration;
        $this->apiClient = $apiClient;

        $this->carrierProductCache = [];
        $this->servicePointTypeCache = [];
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
        if (isset($this->carrierProductCache[$carrierCode])) {
            $cached = $this->carrierProductCache[$carrierCode];

            if (is_array($cached) && isset($cached['value'], $cached['exp']) && (int) $cached['exp'] > time()) {
                $value = $cached['value'];

                if (is_array($value)) {
                    return $value;
                }
            }
        }

        $value = $this->apiClient->getCarrierProducts($carrierCode);

        $this->carrierProductCache[$carrierCode] = ['exp' => time() + 1800, 'value' => $value];

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
        $expirationTime = $this->configuration->get('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION');

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
        if (isset($this->servicePointTypeCache[$productCode])) {
            $cached = $this->servicePointTypeCache[$productCode];

            if (is_array($cached) && isset($cached['value'], $cached['exp']) && (int) $cached['exp'] > time()) {
                $value = $cached['value'];

                if (is_array($value)) {
                    return $value;
                }
            }
        }

        $value = $this->apiClient->getCarrierProductServicePointTypes($productCode);

        $this->servicePointTypeCache[$productCode] = ['exp' => time() + 1800, 'value' => $value];

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
        foreach ($allProductTypes as $product) {
            if (!$productCode) {
                $productCode = $product->code;
            }

            $productCodeChoices[$product->name] = $product->code;
        }

        $isServicePointDelivery = $productCode === 'service_point';

        $allCarrierProducts = $isServicePointDelivery ? $this->getCarrierProducts($carrierCode) : [];

        $applicableCarrierProductChoices = [];

        $validCarrierProductCode = false;
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
            }
        }

        if (!$validCarrierProductCode) {
            $carrierProductCode = null;

            foreach ($allCarrierProducts as $product) {
                if (!isset($product->service_point_product) || $product->service_point_product !== true) {
                    continue;
                }

                $carrierProductCode = $product->product_code;
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
        ];
    }
}
