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

function upgrade_module_1_1_2($object)
{
    $is_successful = true;

    if (!$object->isRegisteredInHook('displayFooter')) {
        $is_successful = $is_successful && $object->registerHook('displayFooter');
    }

    if ($object->isRegisteredInHook('footer')) {
        $is_successful = $is_successful && $object->unregisterHook('footer');
    }

    if ($object->isRegisteredInHook('header')) {
        $is_successful = $is_successful && $object->unregisterHook('header');
    }

    return $is_successful;
}
