<?php

namespace Shipmondo\Grid\Data\Factory;

use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use Shipmondo\Entity\ShipmondoCarrier;
use Carrier;

final class ShipmondoCarrierGridDataFactoryDecorator implements GridDataFactoryInterface
{
    /**
     * @var GridDataFactoryInterface
     */
    private $shipmondoCarrierGridDataFactory;

    public function __construct(
        GridDataFactoryInterface $shipmondoCarrierGridDataFactory
    ) {
        $this->shipmondoCarrierGridDataFactory = $shipmondoCarrierGridDataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(SearchCriteriaInterface $searchCriteria)
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
    private function applyModifications(RecordCollectionInterface $carriers)
    {
        $availableCarriers = ShipmondoCarrier::getAvailableCarriers();

        foreach ($carriers as $carrier) {
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

        return new RecordCollection($modifiedCarriers);
    }
}