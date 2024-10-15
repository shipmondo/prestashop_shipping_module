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

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use Doctrine\ORM\EntityManager;
use Shipmondo\Entity\ShipmondoCarrier;

function upgradeModule_2_0_0($module)
{
    // TODO not done

    /** @var Db $dbInstance */
    $dbInstance = Db::getInstance();

    /** @var TabRepository $tabRepository */
    $tabRepository = $module->get('prestashop.core.admin.tab.repository');

    /** @var EntityManager $entityManager */
    $entityManager = $module->get('doctrine.orm.entity_manager');

    return removeOldHooks($module)
        && addNewHooks($module)
        && createCarrierTable_2_0_0($dbInstance)
        && createServicePointTable_2_0_0($dbInstance)
        && dropSelectedServicePointsTable_2_0_0($dbInstance)
        && migrateCarriers_2_0_0($entityManager, $module)
        && installTabs_2_0_0($tabRepository, $module);
}

function removeOldHooks($module)
{
    $isSuccessful = true;

    foreach(getHooksToRemove_2_0_0() as $hook) {
        $isSuccessful = $isSuccessful && $module->unregisterHook($hook);
        if (!$isSuccessful) {
            return false;
        }
    }

    return $isSuccessful;
}

function addNewHooks($module)
{
    $isSuccessful = true;

    foreach(getHooksToAdd_2_0_0() as $hook) {
        $isSuccessful = $isSuccessful && $module->registerHook($hook);
        if (!$isSuccessful) {
            return false;
        }
    }

    return $isSuccessful;
}

function migrateCarriers_2_0_0(EntityManager $entityManager, Shipmondo $module)
{
    $isSuccessful = true;

    $legacyCarrierMap = getLegacyCarrierMap_2_0_0();
    foreach ($legacyCarrierMap as $carrierName => $carrierDetails) {
        $carrier = $module->getCarrierByReference($carrierName);

        if ($carrier) {
            $isSuccessful = $isSuccessful && migrateCarrier_2_0_0($entityManager, $carrier, $carrierDetails);
            if (!$isSuccessful) {
                return false;
            }
        }
    }

    $legacyServicePointCarriersMap = getLegacyServicePointCarrierMap_2_0_0();
    foreach ($legacyServicePointCarriersMap as $carrierReference => $carrierDetails) {
        $carrier = $module->getCarrierByReference($carrierReference);

        if ($carrier) {
            $isSuccessful = $isSuccessful && migrateCarrier_2_0_0($entityManager, $carrier, $carrierDetails);
            if (!$isSuccessful) {
                return false;
            }
        }
    }

    return $isSuccessful;
}

function migrateCarrier_2_0_0(EntityManager $entityManager, Carrier $carrier, array $carrierDetails): bool
{
    if ($carrier) {
        \PrestaShopLogger::addLog('Create ShipmondoCarrier for ' . $carrier->name, 1, null, 'Shipmondo');
        $shipmondoCarrier = new ShipmondoCarrier();
        $shipmondoCarrier->setCarrierId($carrier->id);
        $shipmondoCarrier->setCarrierCode($carrierDetails['carrier_code']);
        $shipmondoCarrier->setProductCode($carrierDetails['product_code']);
        $entityManager->persist($shipmondoCarrier);
        $entityManager->flush();
        \PrestaShopLogger::addLog('ShipmondoCarrier for ' . $carrier->name . ' created', 1, null, 'Shipmondo');
    }

    return true;
}

function createCarrierTable_2_0_0(Db $dbInstance)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_carrier` ('
        . '`id_smd_carrier` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . '`id_carrier` INT NOT NULL, '
        . '`carrier_code` VARCHAR(255) NOT NULL, '
        . '`product_code` VARCHAR(255) NOT NULL'
        . ')';

    return $dbInstance->execute($sql);
}

function createServicePointTable_2_0_0(Db $dbInstance)
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

function dropSelectedServicePointsTable_2_0_0(\Db $dbInstance)
{
    $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'shipmondo_selected_service_points`';

    return $dbInstance->execute($sql);
}

function installTabs_2_0_0(TabRepository $tabRepository, Shipmondo $module): bool
{
    $translator = $module->getTranslator();

    foreach (getTabs_2_0_0() as $tab) {
        $tabId = $tabRepository->findOneIdByClassName($tab['class_name']);

        if (!$tabId) {
            $tabId = null;
        }

        $newTab = new Tab($tabId);
        $newTab->active = $tab['visible'];
        $newTab->class_name = $tab['class_name'];

        $newTab->route_name = $tab['route_name'];
        $newTab->id_parent = $tabRepository->findOneIdByClassName($tab['parent_class_name']);
        $newTab->wording = $tab['wording'];
        $newTab->wording_domain = $tab['wording_domain'];
        $newTab->name = [];

        foreach (Language::getLanguages() as $lang) {
            $newTab->name[$lang['id_lang']] = $translator->trans($tab['name'], [], 'Modules.Shipmondo.Admin', $lang['locale']);
        }

        $newTab->module = $module->name;

        if (!$newTab->save()) {
            return false;
        }
    }

    return true;
}

function getTabs_2_0_0(): array
{
    return [
        [
            'name' => 'Shipmondo carriers',
            'class_name' => 'AdminShipmondoShipmondoCarrier',
            'route_name' => 'shipmondo_shipmondo_carriers_search',
            'visible' => true,
            'parent_class_name' => 'AdminParentShipping',
            'wording' => 'Shipmondo carriers',
            'wording_domain' => 'Modules.Shipmondo.Admin',
        ],
        [
            'name' => 'Shipmondo Delivery Checkout',
            'class_name' => 'AdminShipmondoConfiguration',
            'route_name' => 'shipmondo_configuration',
            'visible' => false,
            'parent_class_name' => 'AdminParentModulesSf',
            'wording' => 'Shipmondo Delivery Checkout',
            'wording_domain' => 'Modules.Shipmondo.Admin',
        ],
    ];
}

function getLegacyCarrierMap_2_0_0(): array
{
    return [
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
}

function getLegacyServicePointCarrierMap_2_0_0(): array
{
    return [
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
}

function getHooksToRemove_2_0_0(): array
{
    return [
        'newOrder', // Deprecated alias for actionValidateOrder
    ];
}

function getHooksToAdd_2_0_0(): array
{
    return [
        'displayAdminOrderSide',
        'displayAfterCarrier',
        'actionValidateOrder',
        'addWebserviceResources',
    ];
}

function getLocales_2_0_0(): array
{
    return [
        'da-DK',
        'en-US',
        'sv-SE',
        'nb-NO',
        'nn-NO',
    ];
}

