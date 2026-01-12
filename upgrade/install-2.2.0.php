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

function upgrade_module_2_2_0(Shipmondo $module): bool
{
    $dbInstance = Db::getInstance();

    return addNewColumnsToCarrierTable_2_2_0($dbInstance);
}

function addCarrierProductCodeColumn_2_2_0(Db $dbInstance): bool
{
    $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'shipmondo_carrier` ADD `carrier_product_code` TEXT;';

    return $dbInstance->execute($sql);
}

function addServicePointTypesColumn_2_2_0(Db $dbInstance): bool
{
    $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'shipmondo_carrier` ADD `service_point_types` TEXT;';

    return $dbInstance->execute($sql);
}

function addNewColumnsToCarrierTable_2_2_0(Db $dbInstance): bool
{
    return addCarrierProductCodeColumn_2_2_0($dbInstance) && addServicePointTypesColumn_2_2_0($dbInstance);
}
