<?php
/**
 *  @author    Shipmondo
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo;

use Shipmondo\ApiClient;
use Configuration;

class ShipmondoConfiguration
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get available carriers with available products from Shipmondo
     * @return array
     */
    public function getAvailableCarriers(): array
    {
        $availableCarriers = Configuration::get('SHIPMONDO_AVAILABLE_CARRIERS');
        $expirationTime = Configuration::get('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION');
        if (!$availableCarriers || !$expirationTime || $expirationTime < time()) {
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