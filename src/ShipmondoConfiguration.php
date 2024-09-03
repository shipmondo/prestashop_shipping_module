<?php

namespace Shipmondo;

use Shipmondo\ApiClient;
use Configuration;

class ShipmondoConfiguration
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get the frontend key
     * @return string|null
     */
    public function getFrontendKey()
    {
        return Configuration::get('SHIPMONDO_FRONTEND_KEY');
    }

    /**
     * Get the frontend type
     * @return string|null
     */
    public function getFrontendType()
    {
        return Configuration::get('SHIPMONDO_FRONTEND_TYPE');
    }

    public function getAvailableCarriers()
    {
        $availableCarriers = Configuration::get('SHIPMONDO_AVAILABLE_CARRIERS');
        $expirationTime = Configuration::get('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION');
        if (!$availableCarriers || ($expirationTime && $expirationTime < time())) {
            $availableCarriers = $this->apiClient->getCarriers();

            // Change boolean values to array of products to prepare for the future
            foreach ($availableCarriers as $availableCarrier) {
                $products = [];
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

            Configuration::updateValue('SHIPMONDO_AVAILABLE_CARRIERS', json_encode($availableCarriers));
            Configuration::updateValue('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION', time() + 21600); // TODO Should it be 6 hours?

            return $availableCarriers;
        }

        return json_decode($availableCarriers);
    }
}