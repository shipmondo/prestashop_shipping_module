<?php
/**
 *  @author    Shipmondo
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo\Grid\Data\Factory;

use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use Shipmondo\Entity\ShipmondoCarrier;
use Carrier;
use Shipmondo\Exception\ShipmondoApiException;
use Shipmondo\ShipmondoConfiguration;

final class ShipmondoCarrierGridDataFactoryDecorator implements GridDataFactoryInterface
{
    /**
     * @var GridDataFactoryInterface
     */
    private $shipmondoCarrierGridDataFactory;

    /**
     * @var ShipmondoConfiguration
     */
    private $shipmondoConfiguration;

    public function __construct(
        GridDataFactoryInterface $shipmondoCarrierGridDataFactory,
        ShipmondoConfiguration $shipmondoConfiguration
    ) {
        $this->shipmondoCarrierGridDataFactory = $shipmondoCarrierGridDataFactory;
        $this->shipmondoConfiguration = $shipmondoConfiguration;
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
            $availableCarriers = $this->shipmondoConfiguration->getAvailableCarriers();

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
        } catch (ShipmondoApiException $e) {
            // Use codes as backups if API call fails
            foreach ($carriers as $carrier) {
                $carrier['carrier_name'] = $carrier['carrier_code'];
                $carrier['product_name'] = $carrier['product_code'];
                $modifiedCarriers[] = $carrier;
            }
        }

        return new RecordCollection($modifiedCarriers);
    }
}