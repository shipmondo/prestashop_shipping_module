<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Cache;

/**
 * Small key/value cache storing one file per entry, so it persists across requests without
 * relying on a cache system being enabled in PrestaShop.
 *
 * Expired entries are removed when they are read, and the whole directory is swept at most once
 * per PRUNE_INTERVAL when writing, so entries that are never read again do not pile up.
 */
class FileCache
{
    /**
     * Minimum number of seconds between two sweeps of the cache directory.
     */
    public const PRUNE_INTERVAL = 600;

    private const FILE_EXTENSION = '.cache';

    private const PRUNE_MARKER = '.last_prune';

    /**
     * @var string
     */
    private $directory;

    /**
     * @var callable
     */
    private $clock;

    /**
     * @param string $directory Directory holding the cache files. Created on first write.
     * @param callable|null $clock Returns the current unix timestamp. Defaults to time().
     */
    public function __construct(string $directory, ?callable $clock = null)
    {
        $this->directory = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
        $this->clock = $clock ?? 'time';
    }

    /**
     * Create a cache in a subdirectory of the PrestaShop cache directory (var/cache/<env>/).
     */
    public static function createInPrestaShopCacheDir(string $subdirectory): self
    {
        return new self(_PS_CACHE_DIR_ . 'shipmondo' . DIRECTORY_SEPARATOR . $subdirectory);
    }

    /**
     * Get a cached value, or null if it is missing or has expired.
     */
    public function get(string $key): ?string
    {
        $file = $this->getFilePath($key);

        if (!is_file($file)) {
            return null;
        }

        $contents = @file_get_contents($file);

        if ($contents === false) {
            return null;
        }

        $parts = explode("\n", $contents, 2);

        if (count($parts) !== 2 || (int) $parts[0] <= $this->now()) {
            @unlink($file);

            return null;
        }

        return $parts[1];
    }

    /**
     * Store a value for the given number of seconds. Failing to write is not an error, the value is just not cached.
     */
    public function set(string $key, string $value, int $ttl): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            return;
        }

        // Write to a temporary file first, so concurrent requests never read a partially written entry.
        $temporaryFile = @tempnam($this->directory, 'tmp');

        if ($temporaryFile === false) {
            return;
        }

        $expiresAt = $this->now() + $ttl;

        if (@file_put_contents($temporaryFile, $expiresAt . "\n" . $value) === false
            || !@rename($temporaryFile, $this->getFilePath($key))
        ) {
            @unlink($temporaryFile);

            return;
        }

        $this->pruneIfDue();
    }

    /**
     * Remove all expired entries.
     */
    public function prune(): void
    {
        $files = glob($this->directory . '*' . self::FILE_EXTENSION);

        if ($files === false) {
            return;
        }

        $now = $this->now();

        foreach ($files as $file) {
            $handle = @fopen($file, 'r');

            if ($handle === false) {
                continue;
            }

            $expiresAt = fgets($handle);
            fclose($handle);

            if ($expiresAt === false || (int) $expiresAt <= $now) {
                @unlink($file);
            }
        }

        // Temporary files are renamed right after being written. Leftovers are from interrupted writes.
        $temporaryFiles = glob($this->directory . 'tmp*');

        foreach ($temporaryFiles ?: [] as $temporaryFile) {
            $modifiedAt = @filemtime($temporaryFile);

            if ($modifiedAt !== false && $modifiedAt + self::PRUNE_INTERVAL <= $now) {
                @unlink($temporaryFile);
            }
        }
    }

    private function pruneIfDue(): void
    {
        $marker = $this->directory . self::PRUNE_MARKER;
        $now = $this->now();

        if (is_file($marker) && filemtime($marker) + self::PRUNE_INTERVAL > $now) {
            return;
        }

        @touch($marker, $now);

        $this->prune();
    }

    private function getFilePath(string $key): string
    {
        return $this->directory . hash('sha256', $key) . self::FILE_EXTENSION;
    }

    private function now(): int
    {
        return (int) call_user_func($this->clock);
    }
}
