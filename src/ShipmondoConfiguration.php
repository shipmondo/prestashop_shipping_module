<?php

namespace Shipmondo;

class ShipmondoConfiguration
{
    public function __construct()
    {

    }

    public function getFrontendKey()
    {
        return Configuration::get('SHIPMONDO_FRONTEND_KEY');
    }

    public function getFrontendType()
    {
        return Configuration::get('SHIPMONDO_FRONTEND_TYPE');
    }

    public function getAvailableCarriers()
    {
        $availableCarriers = Configuration::get('SHIPMONDO_AVAILABLE_CARRIERS');
        if (!$availableCarriers) {
            return [];
        }

        return json_decode($availableCarriers);
    }
}