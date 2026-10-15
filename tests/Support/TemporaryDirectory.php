<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Support;

/**
 * Throwaway directories for tests touching the file system.
 */
final class TemporaryDirectory
{
    public static function create(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'shipmondo-test-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);

        return $directory;
    }

    public static function remove(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                self::remove($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
