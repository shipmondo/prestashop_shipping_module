<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Grid\Search;

/**
 * Minimal stand-in for the PrestaShop core interface, used by unit tests only.
 */
interface SearchCriteriaInterface
{
    public function getOrderBy();

    public function getOrderWay();

    public function getOffset();

    public function getLimit();

    public function getFilters();
}
