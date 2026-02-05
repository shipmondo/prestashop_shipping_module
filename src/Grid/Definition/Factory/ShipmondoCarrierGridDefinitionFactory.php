<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Grid\Definition\Factory;

use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\LinkColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ShipmondoCarrierGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    public const GRID_ID = 'shipmondo_carrier';

    /**
     * {@inheritdoc}
     */
    protected function getId(): string
    {
        return self::GRID_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getName(): string
    {
        return $this->trans('Carriers', [], 'Modules.Shipmondo.Admin');
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumns(): ColumnCollection
    {
        return (new ColumnCollection())
            ->add(
                (new DataColumn('id_smd_carrier'))
                    ->setName($this->trans('ID', [], 'Admin.Global'))
                    ->setOptions([
                        'field' => 'id_smd_carrier',
                    ])
            )
            ->add(
                (new LinkColumn('carrier_link'))
                    ->setName($this->trans('Carrier', [], 'Admin.Global'))
                    ->setOptions([
                        'field' => 'ps_carrier_name', // Join on carrier table
                        'route' => 'admin_carriers_edit',
                        'route_param_name' => 'carrierId',
                        'route_param_field' => 'id_carrier',
                    ])
            )
            ->add(
                (new DataColumn('carrier_name'))
                    ->setName($this->trans('Shipmondo carrier', [], 'Modules.Shipmondo.Admin'))
                    ->setOptions([
                        'field' => 'carrier_name', // Set in decorator
                    ])
            )
            ->add(
                (new DataColumn('product_name'))
                    ->setName($this->trans('Product', [], 'Modules.Shipmondo.Admin'))
                    ->setOptions([
                        'field' => 'product_name', // Set in decorator
                    ])
            )
            ->add($this->getCarrierProductCodeColumn())
            ->add($this->getServicePointTypesColumn())
            ->add(
                (new ActionColumn('actions'))
                    ->setName($this->trans('Actions', [], 'Admin.Actions'))
                    ->setOptions([
                        'actions' => (new RowActionCollection())
                            ->add(
                                (new LinkRowAction('edit'))
                                    ->setIcon('edit')
                                    ->setOptions([
                                        'route' => 'shipmondo_shipmondo_carriers_edit',
                                        'route_param_name' => 'id',
                                        'route_param_field' => 'id_smd_carrier',
                                    ])
                            )
                            ->add(
                                (new LinkRowAction('delete'))
                                    ->setName($this->trans('Delete', [], 'Admin.Actions'))
                                    ->setIcon('delete')
                                    ->setOptions([
                                        'route' => 'shipmondo_shipmondo_carriers_delete',
                                        'route_param_name' => 'id',
                                        'route_param_field' => 'id_smd_carrier',
                                        'confirm_message' => $this->trans(
                                            'Delete selected item?',
                                            [],
                                            'Admin.Notifications.Warning'
                                        ),
                                    ])
                            ),
                    ])
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilters(): FilterCollection
    {
        return (new FilterCollection())
            ->add(
                (new Filter('id_smd_carrier', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => $this->trans('ID', [], 'Admin.Global'),
                        ],
                    ])
                    ->setAssociatedColumn('id_smd_carrier')
            )
            ->add(
                (new Filter('actions', SearchAndResetType::class))
                    ->setTypeOptions([
                        'reset_route' => 'admin_common_reset_search_by_filter_id',
                        'reset_route_params' => [
                            'filterId' => self::GRID_ID,
                        ],
                        'redirect_route' => 'shipmondo_shipmondo_carriers_index',
                    ])
                    ->setAssociatedColumn('actions')
            );
    }

    private function getCarrierProductCodeColumn(): DataColumn
    {
        $carrierProductCodeColumn = new DataColumn('carrier_product_code');

        $carrierProductCodeColumn->setName($this->trans('Carrier product', [], 'Modules.Shipmondo.Admin'));
        $carrierProductCodeColumn->setOptions([
            'field' => 'carrier_product_name',
        ]);

        return $carrierProductCodeColumn;
    }

    private function getServicePointTypesColumn(): DataColumn
    {
        $servicePointTypesColumn = new DataColumn('service_point_types');

        $servicePointTypesColumn->setName($this->trans('Service point types', [], 'Modules.Shipmondo.Admin'));
        $servicePointTypesColumn->setOptions([
            'field' => 'service_point_type_names',
        ]);

        return $servicePointTypesColumn;
    }
}
