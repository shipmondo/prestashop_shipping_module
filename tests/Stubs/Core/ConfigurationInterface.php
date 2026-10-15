<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core;

/**
 * Minimal stand-in for the PrestaShop core interface, used by unit tests only.
 */
interface ConfigurationInterface
{
    public function get($key, $default = null, $shopConstraint = null);

    public function set($key, $value, $shopConstraint = null);

    public function has($key, $shopConstraint = null);

    public function remove($key);
}
