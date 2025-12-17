<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;

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
        return $this->apiClient->getCarrierProducts($carrierCode);
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

                // TODO: get dynamically
                $availableCarrier->products = [
                    'private' => true,
                    'business' => true,
                    'service_point' => true,
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
}
