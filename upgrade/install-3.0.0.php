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

function upgrade_module_3_0_0(Shipmondo $module): bool
{
    $dbInstance = Db::getInstance();

    return addNewColumnsToCarrierTable_3_0_0($dbInstance);
}

function addCarrierProductCodeColumn_3_0_0(Db $dbInstance): bool
{
    $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'shipmondo_carrier` ADD `carrier_product_code` TEXT;';

    return $dbInstance->execute($sql);
}

function addServicePointTypesColumn_3_0_0(Db $dbInstance): bool
{
    $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'shipmondo_carrier` ADD `service_point_types` TEXT;';

    return $dbInstance->execute($sql);
}

function addNewColumnsToCarrierTable_3_0_0(Db $dbInstance): bool
{
    $carrierProductColumnExists = false;

    $servicePointTypeColumnExists = false;

    $sql = 'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'shipmondo_carrier`;';

    $result = $dbInstance->executeS($sql);

    foreach ($result as $column) {
        $field = $column['Field'];

        if ($field === 'carrier_product_code') {
            $carrierProductColumnExists = true;
        } elseif ($field === 'service_point_types') {
            $servicePointTypeColumnExists = true;
        }
    }

    return ($carrierProductColumnExists || addCarrierProductCodeColumn_3_0_0($dbInstance)) && ($servicePointTypeColumnExists || addServicePointTypesColumn_3_0_0($dbInstance));
}
