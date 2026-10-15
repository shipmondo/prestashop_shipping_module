<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Grid\Data;

use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollectionInterface;

/**
 * Minimal stand-in for the PrestaShop core class, used by unit tests only.
 */
final class GridData
{
    /**
     * @var RecordCollectionInterface
     */
    private $records;

    /**
     * @var int
     */
    private $recordsTotal;

    /**
     * @var string
     */
    private $query;

    public function __construct(RecordCollectionInterface $records, $recordsTotal, $query = '')
    {
        $this->records = $records;
        $this->recordsTotal = $recordsTotal;
        $this->query = $query;
    }

    public function getRecords()
    {
        return $this->records;
    }

    public function getRecordsTotal()
    {
        return $this->recordsTotal;
    }

    public function getQuery()
    {
        return $this->query;
    }
}
