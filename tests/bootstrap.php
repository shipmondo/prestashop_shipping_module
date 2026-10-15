<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/*
 * Unit tests run without a PrestaShop installation. The PrestaShop core classes the
 * module depends on are replaced by minimal working stand-ins from tests/Stubs.
 * They are only loaded when the real class is not available.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'PrestaShop\\PrestaShop\\Core\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $file = __DIR__ . '/Stubs/Core/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
