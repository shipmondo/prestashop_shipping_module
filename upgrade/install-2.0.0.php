<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0($object)
{
    return true; # TODO implement upgrade

    $is_successful = true;


    return $is_successful;
}

$legacy_carriers = [
    'gls_service_point' => [
        'carrier_code' => 'gls',
        'product_code' => 'service_point'
    ],
    'gls_private' => [
        'carrier_code' => 'gls',
        'product_code' => 'private'
    ],
    'gls_business' => [
        'carrier_code' => 'gls',
        'product_code' => 'business'
    ],
    'postnord_service_point' => [
        'carrier_code' => 'pdk',
        'product_code' => 'service_point'
    ],
    'postnord_private' => [
        'carrier_code' => 'pdk',
        'product_code' => 'private'
    ],
    'postnord_business' => [
        'carrier_code' => 'pdk',
        'product_code' => 'business'
    ],
    'dao_service_point' => [
        'carrier_code' => 'dao',
        'product_code' => 'service_point'
    ],
    'dao_direct' => [
        'carrier_code' => 'dao',
        'product_code' => 'private'
    ],
    'bring_service_point' => [
        'carrier_code' => 'bring',
        'product_code' => 'service_point'
    ],
    'bring_private' => [
        'carrier_code' => 'bring',
        'product_code' => 'private'
    ],
    'bring_business' => [
        'carrier_code' => 'bring',
        'product_code' => 'business'
    ]
];

function migrateCarriers()
{
    /*
    Configuration::updateValue(self::PREFIX . $reference, $carrier->id);

    switch ($reference) {
        case 'gls_service_point':
            Configuration::updateValue('SHIPMONDO_GLS_CARRIER_ID', $carrier->id);
            break;
        case 'postnord_service_point':
            Configuration::updateValue('SHIPMONDO_POSTNORD_CARRIER_ID', $carrier->id);
            break;
        case 'dao_service_point':
            Configuration::updateValue('SHIPMONDO_DAO_CARRIER_ID', $carrier->id);
            break;
        case 'bring_service_point':
            Configuration::updateValue('SHIPMONDO_BRING_CARRIER_ID', $carrier->id);
            break;
    }
    */
}