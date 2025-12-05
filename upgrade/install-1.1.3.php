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

function upgrade_module_1_1_3($module)
{
    $isSuccessful = true;

    if ($module->isRegisteredInHook('displayFooter')) {
        $isSuccessful = $isSuccessful && $module->unregisterHook('displayFooter');
    }

    return $isSuccessful;
}
