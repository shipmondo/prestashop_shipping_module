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
                $carrier['carrier_product_name'] = self::getCarrierProductName(
                    $carrier['carrier_code'] ?? null,
                    $carrier['carrier_product_code'] ?? null
                );

                $carrier['service_point_type_names'] = self::getServicePointNames(
                    $carrier['carrier_product_code'] ?? null,
                    $carrier['service_point_types'] ?? null
                );

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
                $carrier['carrier_product_name'] = $carrier['carrier_product_code'] ?? null;
                $carrier['service_point_type_names'] = null;

                if (isset($carrier['service_point_types'])) {
                    if (is_array($carrier['service_point_types'])) {
                        natcasesort($carrier['service_point_types']);
                        $carrier['service_point_type_names'] = implode(', ', $carrier['service_point_types']);
                    } else {
                        $servicePointTypes = (string) $carrier['service_point_types'];

                        if ($servicePointTypes !== '') {
                            $values = explode(',', $carrier['service_point_type_names']);
                            natcasesort($values);
                            $carrier['service_point_type_names'] = implode(', ', $values);
                        }
                    }
                }

                $modifiedCarriers[] = $carrier;
            }
        }

        return new RecordCollection($modifiedCarriers);
    }

    public function getCarrierProductName(?string $carrierCode, ?string $carrierProductCode): ?string
    {
        if ($carrierProductCode === null) {
            return null;
        }

        if ($carrierCode === null) {
            return $carrierProductCode;
        }

        $carrierProducts = $this->shipmondoCarrierHandler->getCarrierProducts($carrierCode);

        foreach ($carrierProducts as $carrierProduct) {
            if ($carrierProductCode === $carrierProduct->product_code) {
                return $carrierProduct->name;
            }
        }

        return $carrierProductCode;
    }

    private function getServicePointNames(?string $carrierProductCode, $selectedServicePointTypes): ?string
    {
        if ($carrierProductCode === null || $carrierProductCode === '') {
            return null;
        }

        if ($selectedServicePointTypes === null) {
            return null;
        }

        $codes = [];

        if (is_array($selectedServicePointTypes)) {
            $codes = $selectedServicePointTypes;
        } else {
            $selectedServicePointTypes = (string) $selectedServicePointTypes;

            if ($selectedServicePointTypes === '') {
                return null;
            }

            $codes = array_unique(explode(',', $selectedServicePointTypes));
        }

        if (count($codes) === 0) {
            return null;
        }

        $servicePointTypes = $this->shipmondoCarrierHandler->getServicePointTypes($carrierProductCode);

        $names = [];

        foreach ($codes as $code) {
            if ($code === '') {
                continue;
            }

            $name = $code;

            foreach ($servicePointTypes as $servicePointType) {
                if (isset($servicePointType->code) && $servicePointType->code === $code) {
                    $name = isset($servicePointType->name) ? (string) $servicePointType->name : $code;
                    break;
                }
            }

            array_push($names, $name);
        }

        natcasesort($names);

        return implode(', ', $names);
    }
}
