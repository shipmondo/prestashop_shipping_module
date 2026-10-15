<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shipmondo\ApiClient;
use Shipmondo\ShipmondoCarrierHandler;
use Shipmondo\Tests\Support\InMemoryConfiguration;

final class ShipmondoCarrierHandlerTest extends TestCase
{
    /**
     * @var ApiClient&MockObject
     */
    private $apiClient;

    /**
     * @var InMemoryConfiguration
     */
    private $configuration;

    /**
     * @var ShipmondoCarrierHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->configuration = new InMemoryConfiguration();
        $this->handler = new ShipmondoCarrierHandler($this->configuration, $this->apiClient);
    }

    public function testGetCarriersBuildsProductListFromApi(): void
    {
        $this->apiClient->method('getCarriers')->willReturn([
            (object) ['code' => 'gls', 'name' => 'GLS'],
            (object) ['code' => 'dhl', 'name' => 'DHL'],
        ]);
        $this->apiClient->method('getCarrierProductsForCarriers')->willReturn([
            'gls' => [self::servicePointProduct('GLS_PSS', 'GLS Parcel Shop')],
            'dhl' => [(object) ['product_code' => 'DHL_HOME', 'name' => 'DHL Home', 'service_point_product' => false]],
        ]);

        $carriers = $this->handler->getCarriers();

        self::assertSame(['private', 'business', 'service_point'], self::productCodes($carriers[0]));
        self::assertSame(['private', 'business'], self::productCodes($carriers[1]));
        self::assertSame('Service Point', $carriers[0]->products[2]->name);
    }

    public function testGetCarriersTreatsFailingProductLookupAsNoServicePoint(): void
    {
        $this->apiClient->method('getCarriers')->willReturn([(object) ['code' => 'gls', 'name' => 'GLS']]);
        // Carriers whose product request fails are left out of the result.
        $this->apiClient->method('getCarrierProductsForCarriers')->willReturn([]);

        $carriers = $this->handler->getCarriers();

        self::assertSame(['private', 'business'], self::productCodes($carriers[0]));
    }

    public function testGetCarriersFetchesProductsForAllCarriersInOneBatch(): void
    {
        $this->apiClient->method('getCarriers')->willReturn([
            (object) ['code' => 'gls', 'name' => 'GLS'],
            (object) ['code' => 'dhl', 'name' => 'DHL'],
            (object) ['code' => 'pdk', 'name' => 'PostNord'],
        ]);
        $this->apiClient->expects(self::never())->method('getCarrierProducts');
        $this->apiClient->expects(self::once())
            ->method('getCarrierProductsForCarriers')
            ->with(['gls', 'dhl', 'pdk'])
            ->willReturn([]);

        $this->handler->getCarriers();
    }

    public function testGetCarriersOnlyFetchesProductsThatAreNotCached(): void
    {
        $this->configuration->set('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls', json_encode([self::servicePointProduct('GLS_PSS', 'GLS Parcel Shop')]));
        $this->configuration->set('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls_EXPIRATION', time() + 60);

        $this->apiClient->method('getCarriers')->willReturn([
            (object) ['code' => 'gls', 'name' => 'GLS'],
            (object) ['code' => 'dhl', 'name' => 'DHL'],
        ]);
        $this->apiClient->expects(self::once())
            ->method('getCarrierProductsForCarriers')
            ->with(['dhl'])
            ->willReturn(['dhl' => [self::servicePointProduct('DHL_SP', 'DHL Service Point')]]);

        $carriers = $this->handler->getCarriers();

        self::assertSame(['private', 'business', 'service_point'], self::productCodes($carriers[0]));
        self::assertSame(['private', 'business', 'service_point'], self::productCodes($carriers[1]));
        self::assertSame('DHL_SP', json_decode($this->configuration->get('SHIPMONDO_CARRIER_PRODUCTS_CACHE_dhl'))[0]->product_code);
        self::assertGreaterThan(time(), (int) $this->configuration->get('SHIPMONDO_CARRIER_PRODUCTS_CACHE_dhl_EXPIRATION'));
    }

    public function testGetCarriersSkipsProductRequestsWhenAllAreCached(): void
    {
        $this->configuration->set('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls', json_encode([]));
        $this->configuration->set('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls_EXPIRATION', time() + 60);

        $this->apiClient->method('getCarriers')->willReturn([(object) ['code' => 'gls', 'name' => 'GLS']]);
        $this->apiClient->expects(self::never())->method('getCarrierProductsForCarriers');

        self::assertSame(['private', 'business'], self::productCodes($this->handler->getCarriers()[0]));
    }

    public function testGetCarriersCachesResultInConfiguration(): void
    {
        $this->apiClient->expects(self::once())->method('getCarriers')->willReturn([(object) ['code' => 'gls', 'name' => 'GLS']]);
        $this->apiClient->method('getCarrierProductsForCarriers')->willReturn([]);

        $this->handler->getCarriers();

        self::assertNotEmpty($this->configuration->get('SHIPMONDO_AVAILABLE_CARRIERS'));
        self::assertGreaterThan(time(), (int) $this->configuration->get('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION'));

        // A new handler instance must read the cache instead of calling the API again.
        $carriers = (new ShipmondoCarrierHandler($this->configuration, $this->apiClient))->getCarriers();

        self::assertSame('gls', $carriers[0]->code);
    }

    public function testGetCarriersRefetchesWhenCacheHasExpired(): void
    {
        $this->configuration->set('SHIPMONDO_AVAILABLE_CARRIERS', json_encode([(object) ['code' => 'old', 'name' => 'Old', 'products' => []]]));
        $this->configuration->set('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION', time() - 1);

        $this->apiClient->expects(self::once())->method('getCarriers')->willReturn([(object) ['code' => 'gls', 'name' => 'GLS']]);
        $this->apiClient->method('getCarrierProductsForCarriers')->willReturn([]);

        self::assertSame('gls', $this->handler->getCarriers()[0]->code);
    }

    public function testGetCarrierProductsUsesFreshCache(): void
    {
        $this->configuration->set('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls', json_encode([self::servicePointProduct('GLS_PSS', 'GLS Parcel Shop')]));
        $this->configuration->set('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls_EXPIRATION', time() + 60);

        $this->apiClient->expects(self::never())->method('getCarrierProducts');

        $products = $this->handler->getCarrierProducts('gls');

        self::assertSame('GLS_PSS', $products[0]->product_code);
    }

    public function testGetCarrierProductsStoresApiResultWhenCacheIsMissing(): void
    {
        $this->apiClient->expects(self::once())
            ->method('getCarrierProducts')
            ->with('gls')
            ->willReturn([self::servicePointProduct('GLS_PSS', 'GLS Parcel Shop')]);

        $this->handler->getCarrierProducts('gls');

        self::assertNotEmpty($this->configuration->get('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls'));
        self::assertGreaterThan(time(), (int) $this->configuration->get('SHIPMONDO_CARRIER_PRODUCTS_CACHE_gls_EXPIRATION'));
    }

    public function testGetCarrierNameFallsBackToCode(): void
    {
        $this->givenCachedCarriers();

        self::assertSame('GLS', $this->handler->getCarrierName('gls'));
        self::assertSame('unknown', $this->handler->getCarrierName('unknown'));
    }

    public function testGetProductNameHumanizesCode(): void
    {
        self::assertSame('Service Point', $this->handler->getProductName('service_point'));
    }

    public function testFormValuesDefaultToFirstCarrierAndProduct(): void
    {
        $this->givenCachedCarriers();

        $values = $this->handler->getCarrierFormValues(null, null, null, null);

        self::assertSame(['GLS' => 'gls', 'DHL' => 'dhl'], $values['choices']['carrier_code']);
        self::assertSame('gls', $values['default']['carrier_code']);
        self::assertSame('private', $values['default']['product_code']);
        self::assertSame([], $values['choices']['carrier_product_code']);
        self::assertNull($values['default']['carrier_product_code']);
    }

    public function testFormValuesFallBackToPrivateForUnknownProduct(): void
    {
        $this->givenCachedCarriers();

        $values = $this->handler->getCarrierFormValues('dhl', 'service_point', null, ['locker']);

        self::assertSame('private', $values['default']['product_code']);
        self::assertNull($values['default']['service_point_types']);
    }

    public function testFormValuesForServicePointListOnlyServicePointProducts(): void
    {
        $this->givenCachedCarriers();
        $this->apiClient->method('getCarrierProducts')->willReturn([
            (object) ['product_code' => 'GLS_HOME', 'name' => 'GLS Home', 'service_point_product' => false],
            self::servicePointProduct('GLS_PSS', 'GLS Parcel Shop', ['DK', 'SE']),
            self::servicePointProduct('GLS_LOCKER', 'GLS Locker', ['DK']),
        ]);
        $this->apiClient->method('getCarrierProductServicePointTypes')
            ->with('GLS_LOCKER')
            ->willReturn([(object) ['code' => 'locker', 'name' => 'Locker']]);

        $values = $this->handler->getCarrierFormValues('gls', 'service_point', 'GLS_LOCKER', null);

        self::assertSame(['GLS Parcel Shop' => 'GLS_PSS', 'GLS Locker' => 'GLS_LOCKER'], $values['choices']['carrier_product_code']);
        self::assertSame('GLS_LOCKER', $values['default']['carrier_product_code']);
        self::assertSame(['Locker' => 'locker'], $values['choices']['service_point_types']);
        self::assertSame([], $values['default']['service_point_types']);
        self::assertSame(['DK'], $values['receiver_countries']);
    }

    public function testFormValuesReplaceInvalidCarrierProductWithFirstServicePointProduct(): void
    {
        $this->givenCachedCarriers();
        $this->apiClient->method('getCarrierProducts')->willReturn([
            self::servicePointProduct('GLS_PSS', 'GLS Parcel Shop', ['DK', 'SE']),
        ]);
        $this->apiClient->method('getCarrierProductServicePointTypes')->willReturn([]);

        $values = $this->handler->getCarrierFormValues('gls', 'service_point', 'REMOVED_PRODUCT', null);

        self::assertSame('GLS_PSS', $values['default']['carrier_product_code']);
        self::assertSame(['DK', 'SE'], $values['receiver_countries']);
    }

    private function givenCachedCarriers(): void
    {
        $carriers = [
            (object) [
                'code' => 'gls',
                'name' => 'GLS',
                'products' => [
                    (object) ['code' => 'private', 'name' => 'Private'],
                    (object) ['code' => 'business', 'name' => 'Business'],
                    (object) ['code' => 'service_point', 'name' => 'Service Point'],
                ],
            ],
            (object) [
                'code' => 'dhl',
                'name' => 'DHL',
                'products' => [
                    (object) ['code' => 'private', 'name' => 'Private'],
                    (object) ['code' => 'business', 'name' => 'Business'],
                ],
            ],
        ];

        $this->configuration->set('SHIPMONDO_AVAILABLE_CARRIERS', json_encode($carriers));
        $this->configuration->set('SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION', time() + 60);
    }

    private static function servicePointProduct(string $code, string $name, array $receiverCountries = []): object
    {
        return (object) [
            'product_code' => $code,
            'name' => $name,
            'service_point_product' => true,
            'receiver_countries' => $receiverCountries,
        ];
    }

    private static function productCodes(object $carrier): array
    {
        return array_map(static function ($product) {
            return $product->code;
        }, $carrier->products);
    }
}
