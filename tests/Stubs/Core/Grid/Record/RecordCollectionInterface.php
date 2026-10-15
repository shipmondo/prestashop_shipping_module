<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Grid\Record;

/**
 * Minimal stand-in for the PrestaShop core interface, used by unit tests only.
 */
interface RecordCollectionInterface extends \IteratorAggregate, \Countable
{
    public function all();
}
