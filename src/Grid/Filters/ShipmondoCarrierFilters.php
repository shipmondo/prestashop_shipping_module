<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Grid\Filters;

use PrestaShop\PrestaShop\Core\Search\Filters;
use Shipmondo\Grid\Definition\Factory\ShipmondoCarrierGridDefinitionFactory;

class ShipmondoCarrierFilters extends Filters
{
    protected $filterId = ShipmondoCarrierGridDefinitionFactory::GRID_ID;

    /**
     * {@inheritdoc}
     */
    public static function getDefaults(): array
    {
        return [
            'limit' => 10,
            'offset' => 0,
            'orderBy' => 'id_smd_carrier',
            'sortOrder' => 'asc',
            'filters' => [],
        ];
    }
}
