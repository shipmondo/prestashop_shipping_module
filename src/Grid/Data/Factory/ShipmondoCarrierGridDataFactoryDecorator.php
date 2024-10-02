<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Grid\Data\Factory;

use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use Shipmondo\Entity\ShipmondoCarrier;
use Carrier;
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

    public function __construct(
        GridDataFactoryInterface $shipmondoCarrierGridDataFactory,
        ShipmondoCarrierHandler $shipmondoCarrierHandler
    ) {
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