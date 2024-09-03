<?php

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
    public function getCarriers()
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
     * @return object|null
     */
    public function getCarrier(string $carrierCode)
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
    public function getProducts(string $carrierCode)
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
    public function getCarrierName(string $carrierCode)
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
    public function getProductName(string $productCode)
    {
        return ucwords(str_replace('_', ' ', $productCode));
    }




}