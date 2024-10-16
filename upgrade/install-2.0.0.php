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

use PrestaShopBundle\Entity\Repository\TabRepository;

function upgrade_module_2_0_0($module)
{
    /** @var Db $dbInstance */
    $dbInstance = Db::getInstance();

    /** @var TabRepository $tabRepository */
    $tabRepository = $module->get('prestashop.core.admin.tab.repository');

    return removeOldHooks_2_0_0($module)
        && addNewHooks_2_0_0($module)
        && migrateFrontendType_2_0_0()
        && createCarrierTable_2_0_0($dbInstance)
        && createServicePointTable_2_0_0($dbInstance)
        && dropSelectedServicePointsTable_2_0_0($dbInstance)
        && migrateCarriers_2_0_0($dbInstance, $module)
        && installTabs_2_0_0($tabRepository, $module)
        && unlinkFiles_2_0_0();
}

function removeOldHooks_2_0_0($module): bool
{
    foreach (getHooksToRemove_2_0_0() as $hook) {
        if (!$module->unregisterHook($hook)) {
            return false;
        }
    }

    return true;
}

function addNewHooks_2_0_0($module): bool
{
    foreach (getHooksToAdd_2_0_0() as $hook) {
        if (!$module->registerHook($hook)) {
            return false;
        }
    }

    return true;
}

/**
 * Migrate radio buttons to dropdown as radio buttons has been removed
 *
 * @return bool
 */
function migrateFrontendType_2_0_0(): bool
{
    $frontendType = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
    if ($frontendType == 'radio') {
        Configuration::updateValue('SHIPMONDO_FRONTEND_TYPE', 'dropdown');
    }

    return true;
}

/**
 * Migrate carriers created by older versions of the module to the new format
 *
 * @param Db $dbInstance
 * @param Shipmondo $module
 *
 * @return bool
 */
function migrateCarriers_2_0_0(Db $dbInstance, Shipmondo $module): bool
{
    $migratedCarrierIds = [];

    $legacyCarrierMap = getLegacyCarrierMap_2_0_0();
    foreach ($legacyCarrierMap as $configurationKey => $carrierDetails) {
        $carrierId = Configuration::get($configurationKey);
        $carrier = Carrier::getCarrierByReference($carrierId);

        if (!$carrier || in_array($carrier->id, $migratedCarrierIds)) {
            Configuration::deleteByName($configurationKey);
            continue;
        }

        if (migrateCarrier_2_0_0($dbInstance, $carrier, $carrierDetails)) {
            $migratedCarrierIds[] = $carrier->id;
            Configuration::deleteByName($configurationKey);
        } else {
            return false;
        }
    }

    return true;
}

/**
 * Migrate a single carrier to the new format
 *
 * @param Db $dbInstance
 * @param Carrier $carrier
 * @param array $carrierDetails
 *
 * @return bool
 */
function migrateCarrier_2_0_0(Db $dbInstance, Carrier $carrier, array $carrierDetails): bool
{
    if ($carrier) {
        if ($carrier->deleted) {
            return true;
        }

        $dbInstance->insert('shipmondo_carrier', [
            'id_carrier' => $carrier->id,
            'carrier_code' => $carrierDetails['carrier_code'],
            'product_code' => $carrierDetails['product_code'],
        ]);
    }

    return true;
}

function createCarrierTable_2_0_0(Db $dbInstance)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' .
     _DB_PREFIX_ . 'shipmondo_carrier` ('
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

function dropSelectedServicePointsTable_2_0_0(Db $dbInstance)
{
    $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'shipmondo_selected_service_points`';

    return $dbInstance->execute($sql);
}

/**
 * Create new tabs
 *
 * @param TabRepository $tabRepository
 * @param Shipmondo $module
 *
 * @return bool
 */
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
        $newTab->module = $module->name;

        $newTab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $newTab->name[$lang['id_lang']] = $translator->trans($tab['name'], [], 'Modules.Shipmondo.Admin', $lang['locale']);
        }

        if (!$newTab->save()) {
            return false;
        }
    }

    return true;
}

function unlinkFiles_2_0_0(): bool
{
    foreach (getRemovedFiles_2_0_0() as $file) {
        $filePath = __DIR__ . '/../' . $file;
        PrestaShopLogger::addLog('Removing file: ' . $filePath);
        if (file_exists($filePath) && !unlink($filePath)) {
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
        'shipmondo_gls_service_point' => [
            'carrier_code' => 'gls',
            'product_code' => 'service_point',
        ],
        'shipmondo_gls_private' => [
            'carrier_code' => 'gls',
            'product_code' => 'private',
        ],
        'shipmondo_gls_business' => [
            'carrier_code' => 'gls',
            'product_code' => 'business',
        ],
        'shipmondo_postnord_service_point' => [
            'carrier_code' => 'pdk',
            'product_code' => 'service_point',
        ],
        'shipmondo_postnord_private' => [
            'carrier_code' => 'pdk',
            'product_code' => 'private',
        ],
        'shipmondo_postnord_business' => [
            'carrier_code' => 'pdk',
            'product_code' => 'business',
        ],
        'shipmondo_dao_service_point' => [
            'carrier_code' => 'dao',
            'product_code' => 'service_point',
        ],
        'shipmondo_dao_direct' => [
            'carrier_code' => 'dao',
            'product_code' => 'private',
        ],
        'shipmondo_bring_service_point' => [
            'carrier_code' => 'bring',
            'product_code' => 'service_point',
        ],
        'shipmondo_bring_private' => [
            'carrier_code' => 'bring',
            'product_code' => 'private',
        ],
        'shipmondo_bring_business' => [
            'carrier_code' => 'bring',
            'product_code' => 'business',
        ],
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

function getRemovedFiles_2_0_0(): array
{
    return [
        'translations/da.php',
        'views/css/module/onepagecheckoutps.css',
        'views/css/module/supercheckout.css',
        'views/css/module/supercheckout_pre7.css',
        'views/css/module/thecheckout.css',
        'views/css/theme/warehouse.css',
        'views/img/bring.png',
        'views/img/carrier_logos/bring.jpg',
        'views/img/carrier_logos/dao.jpg',
        'views/img/carrier_logos/gls.jpg',
        'views/img/carrier_logos/postnord.jpg',
        'views/img/dao.png',
        'views/img/gls.png',
        'views/img/pdk.png',
        'views/img/shipmondo_logo.png',
        'views/js/module/easycheckout.js',
        'views/js/module/onepagecheckoutps.js',
        'views/js/module/supercheckout_pre7.js',
        'views/js/module/thecheckout.js',
        'views/templates/front/_partials/close_button.tpl',
        'views/templates/front/dropdown/error.tpl',
        'views/templates/front/dropdown/selection_button.tpl',
        'views/templates/front/popup/error.tpl',
        'views/templates/front/popup/modal.tpl',
        'views/templates/front/popup/selection_button.tpl',
        'views/templates/front/radio/content.tpl',
        'views/templates/front/radio/index.php',
        'views/templates/front/radio/selection_button.tpl',
    ];
}
