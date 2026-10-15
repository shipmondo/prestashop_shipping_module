<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Shipmondo\Cache\FileCache;
use Shipmondo\Tests\Support\TemporaryDirectory;

final class FileCacheTest extends TestCase
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var int
     */
    private $now;

    /**
     * @var FileCache
     */
    private $cache;

    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
        $this->now = time();
        $this->cache = $this->createCache($this->directory . '/entries');
    }

    protected function tearDown(): void
    {
        TemporaryDirectory::remove($this->directory);
    }

    public function testReturnsNullForMissingEntry(): void
    {
        self::assertNull($this->cache->get('missing'));
    }

    public function testReturnsStoredValueUntilItExpires(): void
    {
        $this->cache->set('key', 'value', 300);

        $this->now += 299;
        self::assertSame('value', $this->createCache($this->directory . '/entries')->get('key'));

        ++$this->now;
        self::assertNull($this->cache->get('key'));
        self::assertSame([], $this->cacheFiles());
    }

    public function testKeepsValuesSeparatePerKey(): void
    {
        $this->cache->set('first', 'one', 300);
        $this->cache->set('second', "two\nlines", 300);

        self::assertSame('one', $this->cache->get('first'));
        self::assertSame("two\nlines", $this->cache->get('second'));
    }

    public function testPrunesExpiredEntriesThatAreNeverReadAgain(): void
    {
        $this->cache->set('old', 'value', 300);

        $this->now += FileCache::PRUNE_INTERVAL;
        $this->cache->set('new', 'value', 300);

        self::assertCount(1, $this->cacheFiles());
        self::assertSame('value', $this->cache->get('new'));
    }

    public function testDoesNotSweepDirectoryOnEveryWrite(): void
    {
        $this->cache->set('first', 'value', 10);

        $this->now += 10;
        $this->cache->set('second', 'value', 300);

        // The expired entry is left until the next sweep is due.
        self::assertCount(2, $this->cacheFiles());

        $this->now += FileCache::PRUNE_INTERVAL;
        $this->cache->prune();

        self::assertCount(0, $this->cacheFiles());
    }

    private function createCache(string $directory): FileCache
    {
        return new FileCache($directory, function (): int {
            return $this->now;
        });
    }

    private function cacheFiles(): array
    {
        return glob($this->directory . '/entries/*.cache') ?: [];
    }
}
