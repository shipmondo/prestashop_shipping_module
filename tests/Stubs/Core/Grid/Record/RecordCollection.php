<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Grid\Record;

/**
 * Minimal stand-in for the PrestaShop core class, used by unit tests only.
 */
final class RecordCollection implements RecordCollectionInterface
{
    /**
     * @var array
     */
    private $records;

    public function __construct(array $records = [])
    {
        $this->records = $records;
    }

    public function all()
    {
        return $this->records;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->records);
    }

    public function count(): int
    {
        return count($this->records);
    }
}
