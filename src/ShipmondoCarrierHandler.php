<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo;

use Shipmondo\ShipmondoConfiguration;

class ShipmondoCarrierHandler
{
    /**
     * @var ShipmondoConfiguration
     */
    private $shipmondoConfiguration;

    /**
     * @var array
     */
    private $carriers;

    /**
     * @param ShipmondoConfiguration $shipmondoConfiguration
     */
    public function __construct(ShipmondoConfiguration $shipmondoConfiguration)
    {
        $this->shipmondoConfiguration = $shipmondoConfiguration;
    }


    /**
     * Get available carriers from Shipmondo
     *
     * @return array
     */
    public function getCarriers(): array
    {
        if (!$this->carriers) {
            $this->carriers = $this->shipmondoConfiguration->getAvailableCarriers();
        }

        return $this->carriers;
    }

    /**
     * Get carrier by code
     *
     * @param string $carrierCode
     * @return ?object
     */
    public function getCarrier(string $carrierCode): ?object
    {
        $carriers = self::getCarriers();

        foreach($carriers as $carrier) {
            if ($carrier->code === $carrierCode) {
                return $carrier;
            }
        }

        return null;
    }

    /**
     * Get products for a carrier
     *
     * @param string $carrierCode
     * @return array
     */
    public function getProducts(string $carrierCode): array
    {
        $carriers = self::getCarriers();

        foreach($carriers as $carrier) {
            if ($carrier->code === $carrierCode) {
                return $carrier->products;
            }
        }

        return [];
    }

    /**
     * Get carrier name. Fallback to carrier code if name is not found.
     *
     * @param string $carrierCode
     * @return string
     */
    public function getCarrierName(string $carrierCode): string
    {
        $carrier = self::getCarrier($carrierCode);
        return $carrier ? $carrier->name : $carrierCode;
    }

    /**
     * Get product name.
     *
     * @param string $productCode
     * @return string
     */
    public function getProductName(string $productCode): string
    {
        return ucwords(str_replace('_', ' ', $productCode));
    }
}