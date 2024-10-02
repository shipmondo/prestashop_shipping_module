<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Doctrine\ORM\EntityManager;
use Shipmondo\Entity\ShipmondoCarrier;
use Carrier;
use Configuration;
use Shipmondo;

const LEGACY_CARRIER_MAP = [
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

const LEGACY_SERVICE_POINT_MAP = [
    'SHIPMONDO_GLS_CARRIER_ID' => [
        'carrier_code' => 'gls',
        'product_code' => 'service_point'
    ],
    'SHIPMONDO_POSTNORD_CARRIER_ID' => [
        'carrier_code' => 'pdk',
        'product_code' => 'service_point'
    ],
    'SHIPMONDO_DAO_CARRIER_ID' => [
        'carrier_code' => 'dao',
        'product_code' => 'service_point'
    ],
    'SHIPMONDO_BRING_CARRIER_ID' => [
        'carrier_code' => 'bring',
        'product_code' => 'service_point'
    ]
];

function upgrade_module_2_0_0($module)
{
    return true; // TODO implement upgrade

    $isSuccessful = true;
    $entityManager = $module->get('doctrine.orm.entity_manager');

    $frontendType = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
    if($frontendType === 'radio') {
        // Radio buttons are removed as dropdown not serves the same purpose
        Configuration::updateValue('SHIPMONDO_FRONTEND_TYPE', 'dropdown');
        $frontendType = 'dropdown';
    }

    $migratedCarrierIds = [];
    foreach(self::LEGACY_CARRIER_MAP as $reference => $carrierDetails) {
        $configurationKey = Shipmondo::PREFIX . $reference;
        $carrierId = Configuration::get($configurationKey);
        $carrier = new Carrier($carrierId);

        $isSuccessful &= migrateCarriers($entityManager, $reference, $carrier, $carrierDetails);
        if($isSuccessful) {
            $migratedCarrierIds[] = $carrier->id;
            Configuration::deleteByName($configurationKey);
        } else {
            return false;
        }
    }

    foreach(self::LEGACY_SERVICE_POINT_KEYS as $key => $carrierDetails) {
        $carrierId = Configuration::get($key);
        if(in_array($carrierId, $migratedCarrierIds)) {
            continue; # Already migrated
        }

        $isSuccessful &= migrateCarriers($entityManager, $carrier, $carrierDetails);


        Configuration::deleteByName($key);
    }

    return $isSuccessful;
}

function migrateCarriers(EntityManager $entityManager, Carrier $carrier, array $carrierDetails): bool
{
    if($carrier) {
        $shipmondoCarrier = new ShipmondoCarrier();
        $shipmondoCarrier->setCarrierId($carrier->id);
        $shipmondoCarrier->setCarrierCode($carrierDetails['carrier_code']);
        $shipmondoCarrier->setProductCode($carrierDetails['product_code']);
        $entityManager->persist($shipmondoCarrier);
        $entityManager->flush();
        return true;
    } else {
        return false;
    }
}