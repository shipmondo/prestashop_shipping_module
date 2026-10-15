<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Grid\Data\Factory;

use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

/**
 * Minimal stand-in for the PrestaShop core interface, used by unit tests only.
 */
interface GridDataFactoryInterface
{
    public function getData(SearchCriteriaInterface $searchCriteria);
}
