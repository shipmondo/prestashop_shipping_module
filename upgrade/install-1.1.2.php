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

function upgrade_module_1_1_2($module)
{
    $isSuccessful = true;

    if (!$module->isRegisteredInHook('displayFooter')) {
        $isSuccessful = $isSuccessful && $module->registerHook('displayFooter');
    }

    if ($module->isRegisteredInHook('footer')) {
        $isSuccessful = $isSuccessful && $module->unregisterHook('footer');
    }

    if ($module->isRegisteredInHook('header')) {
        $isSuccessful = $isSuccessful && $module->unregisterHook('header');
    }

    return $isSuccessful;
}
