<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Unit\Grid\Data\Factory;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use Shipmondo\ApiClient;
use Shipmondo\Exception\ShipmondoApiException;
use Shipmondo\Grid\Data\Factory\ShipmondoCarrierGridDataFactoryDecorator;
use Shipmondo\ShipmondoCarrierHandler;
use Shipmondo\Tests\Support\InMemoryConfiguration;

final class ShipmondoCarrierGridDataFactoryDecoratorTest extends TestCase
{
    /**
     * @var ApiClient&MockObject
     */
    private $apiClient;

    /**
     * @var InMemoryConfiguration
     */
    private $configuration;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->configuration = new InMemoryConfiguration();
    }

    public function testResolvesNamesFromShipmondo(): void
    {
        $this->givenCachedCarriers();
        $this->givenCache('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls', [
            (object) ['product_code' => 'GLS_PSS', 'name' => 'GLS Parcel Shop', 'service_point_product' => true],
        ]);
        $this->givenCache('SHIPMONDO_SERVICE_POINT_TYPES_CACHE_GLS_PSS', [
            (object) ['code' => 'parcel_shop', 'name' => 'Parcel shop'],
            (object) ['code' => 'locker', 'name' => 'Locker'],
        ]);

        $data = $this->decorate([
            self::row(1, 'gls', 'service_point', 'GLS_PSS', 'parcel_shop,locker'),
        ]);

        $record = $data->getRecords()->all()[0];

        self::assertSame('GLS', $record['carrier_name']);
        self::assertSame('Service Point', $record['product_name']);
        self::assertSame('GLS Parcel Shop', $record['carrier_product_name']);
        self::assertSame('Locker, Parcel shop', $record['service_point_type_names']);
        self::assertSame(1, $data->getRecordsTotal());
        self::assertSame('SELECT ...', $data->getQuery());
    }

    public function testFallsBackToCodesWhenShipmondoIsUnavailable(): void
    {
        $this->apiClient->method('getCarriers')->willThrowException(new ShipmondoApiException('Unavailable'));

        $data = $this->decorate([
            self::row(1, 'gls', 'private', null, null),
        ]);

        $record = $data->getRecords()->all()[0];

        self::assertSame('gls', $record['carrier_name']);
        self::assertSame('private', $record['product_name']);
        self::assertNull($record['carrier_product_name']);
        self::assertNull($record['service_point_type_names']);
    }

    /**
     * Review finding M1: rows processed before the failing row are added twice.
     */
    #[Group('known-bug')]
    public function testDoesNotDuplicateRowsWhenShipmondoFailsMidway(): void
    {
        $this->givenCachedCarriers();
        $this->apiClient->method('getCarrierProducts')->willThrowException(new ShipmondoApiException('Unavailable'));

        $data = $this->decorate([
            self::row(1, 'gls', 'private', null, null),
            self::row(2, 'dhl', 'service_point', 'DHL_SP', null),
        ]);

        self::assertSame([1, 2], array_column($data->getRecords()->all(), 'id_smd_carrier'));
    }

    /**
     * Review finding M13: the fallback explodes the wrong variable, which throws a TypeError.
     */
    #[Group('known-bug')]
    public function testFallbackShowsServicePointTypeCodes(): void
    {
        $this->apiClient->method('getCarriers')->willThrowException(new ShipmondoApiException('Unavailable'));

        $data = $this->decorate([
            self::row(1, 'gls', 'service_point', 'GLS_PSS', 'parcel_shop,locker'),
        ]);

        self::assertSame('locker, parcel_shop', $data->getRecords()->all()[0]['service_point_type_names']);
    }

    private function decorate(array $rows): GridData
    {
        $inner = $this->createMock(GridDataFactoryInterface::class);
        $inner->method('getData')->willReturn(new GridData(new RecordCollection($rows), count($rows), 'SELECT ...'));

        $decorator = new ShipmondoCarrierGridDataFactoryDecorator(
            $inner,
            new ShipmondoCarrierHandler($this->configuration, $this->apiClient)
        );

        return $decorator->getData($this->createMock(SearchCriteriaInterface::class));
    }

    private function givenCachedCarriers(): void
    {
        $this->givenCache('SHIPMONDO_AVAILABLE_CARRIERS', [
            (object) [
                'code' => 'gls',
                'name' => 'GLS',
                'products' => [
                    (object) ['code' => 'private', 'name' => 'Private'],
                    (object) ['code' => 'service_point', 'name' => 'Service Point'],
                ],
            ],
            (object) [
                'code' => 'dhl',
                'name' => 'DHL',
                'products' => [
                    (object) ['code' => 'service_point', 'name' => 'Service Point'],
                ],
            ],
        ]);
    }

    private function givenCache(string $key, array $value): void
    {
        $this->configuration->set($key, json_encode($value));
        $this->configuration->set($key . '_EXPIRATION', time() + 60);
    }

    /**
     * Mirrors a row as returned by ShipmondoCarrierQueryBuilder.
     */
    private static function row(int $id, string $carrierCode, string $productCode, ?string $carrierProductCode, ?string $servicePointTypes): array
    {
        return [
            'id_smd_carrier' => $id,
            'id_carrier' => $id + 10,
            'carrier_code' => $carrierCode,
            'product_code' => $productCode,
            'carrier_product_code' => $carrierProductCode,
            'service_point_types' => $servicePointTypes,
            'ps_carrier_name' => 'Carrier ' . $id,
        ];
    }
}
