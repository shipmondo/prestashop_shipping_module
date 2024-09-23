<?php
/**
 *  @author    Shipmondo
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo\Grid\Filters;

use Shipmondo\Grid\Definition\Factory\ShipmondoCarrierGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

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