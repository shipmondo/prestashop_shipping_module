<?php

namespace Shipmondo;

class ShipmondoCarrierHandler
{

    /**
     * @var string
     */
    private const AVAILABLE_CARRIERS_CONFIG_KEY = 'SHIPMONDO_AVAILABLE_CARRIERS';

    /**
     * @var string
     */
    private const AVAILABLE_CARRIERS_EXPIRATION_CONFIG_KEY = 'SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION';

    /**
     * @var array
     */
    private $availableCarriers;
    
    /**
     * @var int
     */
    private $expirationTimestamp;

    public function __construct()
    {
        $this->availableCarriers = $this->getAvailableCarriers();
    }


    /**
     * Calls Shipmondo API to get available carriers
     *
     * @return array
     */
    public function getCarriers()
    {
        if (!$this->expirationTimestamp) {
            $this->expirationTimestamp = (int) Configuration::get(self::AVAILABLE_CARRIERS_EXPIRATION_CONFIG_KEY);
        }

        if ($this->availableCarriers && $this->expirationTimestamp > time()) {
            return $this->availableCarriers;
        }

        $carriersJson = Configuration::get(self::AVAILABLE_CARRIERS_CONFIG_KEY);
        if ($carriersJson && $this->expirationTimestamp > time()) {
            $this->availableCarriers = json_decode($carriersJson);
            return $this->availableCarriers;
        }

        $frontendKey = Configuration::get('SHIPMONDO_FRONTEND_KEY');
        $client = new \Shipmondo\ApiClient($frontendKey);
        $carriers = $client->getCarriers();
        if (empty($carriers)) {
            return [];            
        }

        // Change boolean values to array of products to prepare for the future
        foreach ($carriers as $carrier) {
            $products = [];
            foreach ($carrier->products as $productCode => $hasProduct) {
                if ($hasProduct) {
                    $product = new \stdClass();
                    $product->name = ucwords(str_replace('_', ' ', $productCode));
                    $product->code = $productCode;
                    $products[] = $product;
                }
            }
            $carrier->products = $products;
        }
        
        $this->availableCarriers = $carriers;
        // TODO Should we cache shorter than 6 hours?
        $this->expirationTimestamp = time() + 21600; // Cache for 6 hours
        Configuration::updateValue(self::AVAILABLE_CARRIERS_CONFIG_KEY, json_encode($this->availableCarriers));
        Configuration::updateValue(self::AVAILABLE_CARRIERS_EXPIRATION_CONFIG_KEY, $this->expirationTimestamp);

        return $carriers;
    }

        /**
     * Calls Shipmondo API to get available carriers
     *
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



}