<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Grid\Data\Factory;

use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use Shipmondo\Exception\ShipmondoApiException;
use Shipmondo\ShipmondoCarrierHandler;

final class ShipmondoCarrierGridDataFactoryDecorator implements GridDataFactoryInterface
{
    /**
     * @var GridDataFactoryInterface
     */
    private $shipmondoCarrierGridDataFactory;

    /**
     * @var ShipmondoCarrierHandler
     */
    private $shipmondoCarrierHandler;

    public function __construct(GridDataFactoryInterface $shipmondoCarrierGridDataFactory, ShipmondoCarrierHandler $shipmondoCarrierHandler)
    {
        $this->shipmondoCarrierGridDataFactory = $shipmondoCarrierGridDataFactory;
        $this->shipmondoCarrierHandler = $shipmondoCarrierHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(SearchCriteriaInterface $searchCriteria): GridData
    {
        $carrierData = $this->shipmondoCarrierGridDataFactory->getData($searchCriteria);

        $carrierRecords = $this->applyModifications($carrierData->getRecords());

        return new GridData(
            $carrierRecords,
            $carrierData->getRecordsTotal(),
            $carrierData->getQuery()
        );
    }

    /**
     * @param RecordCollectionInterface $carriers
     *
     * @return RecordCollection
     */
    private function applyModifications(RecordCollectionInterface $carriers): RecordCollection
    {
        $modifiedCarriers = [];

        try {
            $availableCarriers = $this->shipmondoCarrierHandler->getCarriers();

            foreach ($carriers as $carrier) {
                if (isset($carrier['carrier_code']) && isset($carrier['carrier_product_code'])) {
                    $carrierProducts = $this->shipmondoCarrierHandler->getCarrierProducts($carrier['carrier_code']);

                    foreach ($carrierProducts as $carrierProduct) {
                        if ($carrier['carrier_product_code'] === $carrierProduct->product_code) {
                            $carrier['carrier_product_name'] = $carrierProduct->name;
                            break;
                        }
                    }
                }

                // TODO: translate service point type names
                $carrier['service_point_type_names'] = $carrier['service_point_types'];

                foreach ($availableCarriers as $availableCarrier) {
                    if ($carrier['carrier_code'] === $availableCarrier->code) {
                        $carrier['carrier_name'] = $availableCarrier->name;

                        foreach ($availableCarrier->products as $product) {
                            if ($carrier['product_code'] === $product->code) {
                                $carrier['product_name'] = $product->name;
                                break;
                            }
                        }

                        break;
                    }
                }

                $modifiedCarriers[] = $carrier;
            }
        } catch (ShipmondoApiException $e) {
            // Use codes as backups if API call fails
            foreach ($carriers as $carrier) {
                $carrier['carrier_name'] = $carrier['carrier_code'];
                $carrier['product_name'] = $carrier['product_code'];
                $carrier['carrier_product_name'] = isset($carrier['carrier_product_code']) ? $carrier['carrier_product_code'] : null;
                $carrier['service_point_type_names'] = isset($carrier['service_point_types']) ? $carrier['service_point_types'] : null;
                $modifiedCarriers[] = $carrier;
            }
        }

        return new RecordCollection($modifiedCarriers);
    }
}
