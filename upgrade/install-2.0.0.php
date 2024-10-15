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

use Carrier;
use Configuration;
use Doctrine\ORM\EntityManager;
use Shipmondo\Entity\ShipmondoCarrier;

const LEGACY_CARRIER_MAP = [
    'gls_service_point' => [
        'carrier_code' => 'gls',
        'product_code' => 'service_point',
    ],
    'gls_private' => [
        'carrier_code' => 'gls',
        'product_code' => 'private',
    ],
    'gls_business' => [
        'carrier_code' => 'gls',
        'product_code' => 'business',
    ],
    'postnord_service_point' => [
        'carrier_code' => 'pdk',
        'product_code' => 'service_point',
    ],
    'postnord_private' => [
        'carrier_code' => 'pdk',
        'product_code' => 'private',
    ],
    'postnord_business' => [
        'carrier_code' => 'pdk',
        'product_code' => 'business',
    ],
    'dao_service_point' => [
        'carrier_code' => 'dao',
        'product_code' => 'service_point',
    ],
    'dao_direct' => [
        'carrier_code' => 'dao',
        'product_code' => 'private',
    ],
    'bring_service_point' => [
        'carrier_code' => 'bring',
        'product_code' => 'service_point',
    ],
    'bring_private' => [
        'carrier_code' => 'bring',
        'product_code' => 'private',
    ],
    'bring_business' => [
        'carrier_code' => 'bring',
        'product_code' => 'business',
    ],
];

const LEGACY_SERVICE_POINT_MAP = [
    'SHIPMONDO_GLS_CARRIER_ID' => [
        'carrier_code' => 'gls',
        'product_code' => 'service_point',
    ],
    'SHIPMONDO_POSTNORD_CARRIER_ID' => [
        'carrier_code' => 'pdk',
        'product_code' => 'service_point',
    ],
    'SHIPMONDO_DAO_CARRIER_ID' => [
        'carrier_code' => 'dao',
        'product_code' => 'service_point',
    ],
    'SHIPMONDO_BRING_CARRIER_ID' => [
        'carrier_code' => 'bring',
        'product_code' => 'service_point',
    ],
];

const HOOKS_TO_REMOVE = [
    'newOrder', // Deprecated alias for actionValidateOrder
];

const HOOKS_TO_ADD = [
    'displayAdminOrderSide',
    'displayAfterCarrier',
    'actionValidateOrder',
    'addWebserviceResources',
];

function upgrade_module_2_0_0($module)
{
    $isSuccessful = true;

    $entityManager = $module->get('doctrine.orm.entity_manager');
    $dbInstance = Db::getInstance();

    // Remove old hooks
    foreach (HOOKS_TO_REMOVE as $hook) {
        $isSuccessful &= $module->unregisterHook($hook);
    }

    // Add new hooks
    foreach (HOOKS_TO_ADD as $hook) {
        $isSuccessful &= $module->registerHook($hook);
    }

    // Create new tables
    $isSuccessful &= createCarrierTable($dbInstance);
    $isSuccessful &= createServicePointTable($dbInstance);

    // Drop old table
    $isSuccessful &= $dbInstance->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'shipmondo_selected_service_point`');

    // Migrate from radio buttons
    $frontendType = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
    if ($frontendType === 'radio') {
        // Radio buttons are removed as dropdown not serves the same purpose
        $frontendType = 'dropdown';
        Configuration::updateValue('SHIPMONDO_FRONTEND_TYPE', $frontendType);
    }

    // Migrate carriers
    $migratedCarrierIds = [];
    foreach (LEGACY_CARRIER_MAP as $reference => $carrierDetails) {
        $configurationKey = 'shipmondo_' . $reference;
        $carrierId = Configuration::get($configurationKey);
        $carrier = new Carrier($carrierId);

        $isSuccessful &= migrateCarriers($entityManager, $reference, $carrier, $carrierDetails);
        if ($isSuccessful) {
            $migratedCarrierIds[] = $carrier->id;
            Configuration::deleteByName($configurationKey);
        }
    }

    foreach (LEGACY_SERVICE_POINT_MAP as $key => $carrierDetails) {
        $carrierId = Configuration::get($key);
        if (in_array($carrierId, $migratedCarrierIds)) {
            continue; // Already migrated
        }

        $isSuccessful &= migrateCarriers($entityManager, $carrier, $carrierDetails);

        Configuration::deleteByName($key);
    }

    return $isSuccessful;
}

function migrateCarriers(EntityManager $entityManager, Carrier $carrier, array $carrierDetails): bool
{
    if ($carrier) {
        $shipmondoCarrier = new ShipmondoCarrier();
        $shipmondoCarrier->setCarrierId($carrier->id);
        $shipmondoCarrier->setCarrierCode($carrierDetails['carrier_code']);
        $shipmondoCarrier->setProductCode($carrierDetails['product_code']);
        $entityManager->persist($shipmondoCarrier);
        $entityManager->flush();
    }

    return true;
}

function createCarrierTable(\Db $dbInstance)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_carrier` ('
        . '`id_smd_carrier` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . '`id_carrier` INT NOT NULL, '
        . '`carrier_code` VARCHAR(255) NOT NULL, '
        . '`product_code` VARCHAR(255) NOT NULL'
        . ')';

    return $dbInstance->execute($sql);
}

function createServicePointTable(\Db $dbInstance)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_service_point` ('
        . 'id_smd_service_point INT AUTO_INCREMENT NOT NULL PRIMARY KEY, '
        . 'id_cart INT, '
        . 'id_order INT, '
        . 'carrier_code VARCHAR(255) NOT NULL, '
        . 'service_point_id VARCHAR(255) NOT NULL, '
        . 'name VARCHAR(255) NOT NULL, '
        . 'address1 VARCHAR(255) NOT NULL, '
        . 'address2 VARCHAR(255), '
        . 'zip_code VARCHAR(255) NOT NULL, '
        . 'city VARCHAR(255) NOT NULL, '
        . 'country_code VARCHAR(2) NOT NULL'
        . ')';

    return $dbInstance->execute($sql);
}
