<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Shipmondo\Entity\ShipmondoCarrier;

final class ShipmondoCarrierTest extends TestCase
{
    public function testServicePointTypesDefaultToEmptyArray(): void
    {
        $carrier = new ShipmondoCarrier();

        self::assertSame([], $carrier->getServicePointTypes());
    }

    public function testSetServicePointTypesRemovesDuplicatesAndNullsAndSorts(): void
    {
        $carrier = (new ShipmondoCarrier())->setServicePointTypes(['parcel_shop', null, 'locker', 'parcel_shop']);

        self::assertSame(['locker', 'parcel_shop'], $carrier->getServicePointTypes());
    }

    public function testSetServicePointTypesRejectsNonStrings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ShipmondoCarrier())->setServicePointTypes(['locker', 42]);
    }

    public function testEmptyCarrierProductCodeIsStoredAsNull(): void
    {
        $carrier = (new ShipmondoCarrier())->setCarrierProductCode('');

        self::assertNull($carrier->getCarrierProductCode());
    }

    public function testDefaultServicePointFieldsClearServicePointDataForNonServicePointProducts(): void
    {
        $carrier = (new ShipmondoCarrier())
            ->setProductCode('private')
            ->setCarrierProductCode('GLS_PSS')
            ->setServicePointTypes(['locker']);

        $carrier->setDefaultServicePointFields();

        self::assertNull($carrier->getCarrierProductCode());
        self::assertSame([], $carrier->getServicePointTypes());
    }

    public function testDefaultServicePointFieldsKeepSelectionForServicePointProducts(): void
    {
        $carrier = (new ShipmondoCarrier())
            ->setProductCode('service_point')
            ->setCarrierProductCode('GLS_PSS')
            ->setServicePointTypes(['locker']);

        $carrier->setDefaultServicePointFields();

        self::assertSame('GLS_PSS', $carrier->getCarrierProductCode());
        self::assertSame(['locker'], $carrier->getServicePointTypes());
    }

    public function testDefaultServicePointFieldsClearTypesWhenServicePointProductHasNoCarrierProduct(): void
    {
        $carrier = (new ShipmondoCarrier())
            ->setProductCode('service_point')
            ->setServicePointTypes(['locker']);

        $carrier->setDefaultServicePointFields();

        self::assertNull($carrier->getCarrierProductCode());
        self::assertSame([], $carrier->getServicePointTypes());
    }
}
