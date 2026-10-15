<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shipmondo\ApiClient;
use Shipmondo\Cache\FileCache;
use Shipmondo\Exception\ShipmondoApiException;
use Shipmondo\ServicePointProvider;
use Shipmondo\Tests\Support\TemporaryDirectory;

final class ServicePointProviderTest extends TestCase
{
    /**
     * @var ApiClient&MockObject
     */
    private $apiClient;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var int
     */
    private $now;

    /**
     * @var ServicePointProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->apiClient->method('getBaseUri')->willReturn('https://app.shipmondo.com/api/public/v3/');
        $this->apiClient->method('getFrontendKey')->willReturn('frontend-key');
        $this->directory = TemporaryDirectory::create();
        $this->now = time();
        $this->provider = $this->createProvider($this->apiClient);
    }

    protected function tearDown(): void
    {
        TemporaryDirectory::remove($this->directory);
    }

    public function testReturnsServicePointsFromApi(): void
    {
        $this->apiClient->expects(self::once())
            ->method('getServicePoints')
            ->with('GLS_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus', 'Street 1')
            ->willReturn([self::servicePoint('1')]);

        $servicePoints = $this->lookUp();

        self::assertSame('1', $servicePoints[0]->id);
    }

    public function testUsesCacheWithinFiveMinutes(): void
    {
        $this->apiClient->expects(self::once())->method('getServicePoints')->willReturn([self::servicePoint('1')]);

        $this->lookUp();
        $this->now += ServicePointProvider::CACHE_TTL - 1;

        // A new provider instance, as on the next request, must read the cache instead of calling the API again.
        $servicePoints = $this->createProvider($this->apiClient)->getServicePoints('GLS_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus', 'Street 1');

        self::assertEquals([self::servicePoint('1')], $servicePoints);
    }

    public function testRefetchesAfterFiveMinutes(): void
    {
        $this->apiClient->expects(self::exactly(2))
            ->method('getServicePoints')
            ->willReturnOnConsecutiveCalls([self::servicePoint('1')], [self::servicePoint('2')]);

        $this->lookUp();
        $this->now += ServicePointProvider::CACHE_TTL;

        self::assertSame('2', $this->lookUp()[0]->id);
    }

    public function testDoesNotCacheFailedRequest(): void
    {
        $this->apiClient->expects(self::exactly(2))
            ->method('getServicePoints')
            ->willReturnOnConsecutiveCalls(
                self::throwException(new ShipmondoApiException('Unavailable')),
                [self::servicePoint('1')]
            );

        try {
            $this->lookUp();
            self::fail('The API exception should be passed on.');
        } catch (ShipmondoApiException $e) {
            self::assertSame('Unavailable', $e->getMessage());
        }

        self::assertSame([], $this->cacheFiles());

        self::assertSame('1', $this->lookUp()[0]->id);
    }

    public function testDoesNotCacheEmptyResult(): void
    {
        $this->apiClient->expects(self::exactly(2))
            ->method('getServicePoints')
            ->willReturnOnConsecutiveCalls([], [self::servicePoint('1')]);

        self::assertSame([], $this->lookUp());
        self::assertSame([], $this->cacheFiles());
        self::assertSame('1', $this->lookUp()[0]->id);
    }

    #[DataProvider('differentLookupProvider')]
    public function testDifferentInputsUseDifferentEntries(string $productCode, ?array $servicePointTypes, string $countryCode, string $zipcode, string $city, string $address): void
    {
        $this->apiClient->expects(self::exactly(2))
            ->method('getServicePoints')
            ->willReturnOnConsecutiveCalls([self::servicePoint('1')], [self::servicePoint('2')]);

        $this->lookUp();
        $servicePoints = $this->provider->getServicePoints($productCode, $servicePointTypes, $countryCode, $zipcode, $city, $address);

        self::assertSame('2', $servicePoints[0]->id);
    }

    public static function differentLookupProvider(): array
    {
        return [
            'product code' => ['DAO_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus', 'Street 1'],
            'service point types' => ['GLS_PSS', ['parcel_locker'], 'DK', '8000', 'Aarhus', 'Street 1'],
            'no service point types' => ['GLS_PSS', null, 'DK', '8000', 'Aarhus', 'Street 1'],
            'country code' => ['GLS_PSS', ['parcel_shop'], 'SE', '8000', 'Aarhus', 'Street 1'],
            'zipcode' => ['GLS_PSS', ['parcel_shop'], 'DK', '8200', 'Aarhus', 'Street 1'],
            'city' => ['GLS_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus N', 'Street 1'],
            'address' => ['GLS_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus', 'Street 2'],
        ];
    }

    public function testDifferentFrontendKeyUsesDifferentEntry(): void
    {
        $this->apiClient->expects(self::once())->method('getServicePoints')->willReturn([self::servicePoint('1')]);
        $this->lookUp();

        $otherApiClient = $this->createMock(ApiClient::class);
        $otherApiClient->method('getBaseUri')->willReturn('https://app.shipmondo.com/api/public/v3/');
        $otherApiClient->method('getFrontendKey')->willReturn('other-frontend-key');
        $otherApiClient->expects(self::once())->method('getServicePoints')->willReturn([self::servicePoint('2')]);

        $servicePoints = $this->createProvider($otherApiClient)->getServicePoints('GLS_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus', 'Street 1');

        self::assertSame('2', $servicePoints[0]->id);
    }

    public function testDifferentApiUrlUsesDifferentEntry(): void
    {
        $this->apiClient->expects(self::once())->method('getServicePoints')->willReturn([self::servicePoint('1')]);
        $this->lookUp();

        $otherApiClient = $this->createMock(ApiClient::class);
        $otherApiClient->method('getBaseUri')->willReturn('http://localhost:8080/');
        $otherApiClient->method('getFrontendKey')->willReturn('frontend-key');
        $otherApiClient->expects(self::once())->method('getServicePoints')->willReturn([self::servicePoint('2')]);

        $servicePoints = $this->createProvider($otherApiClient)->getServicePoints('GLS_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus', 'Street 1');

        self::assertSame('2', $servicePoints[0]->id);
    }

    public function testInputsDifferingOnlyInSurroundingWhitespaceShareEntry(): void
    {
        $this->apiClient->expects(self::once())->method('getServicePoints')->willReturn([self::servicePoint('1')]);

        $this->lookUp();
        $servicePoints = $this->provider->getServicePoints('GLS_PSS', ['parcel_shop'], ' DK ', ' 8000', 'Aarhus ', "Street 1\n");

        self::assertSame('1', $servicePoints[0]->id);
    }

    private function createProvider(ApiClient $apiClient): ServicePointProvider
    {
        $cache = new FileCache($this->directory, function (): int {
            return $this->now;
        });

        return new ServicePointProvider($apiClient, $cache);
    }

    private function cacheFiles(): array
    {
        return glob($this->directory . '/*.cache') ?: [];
    }

    private function lookUp(): array
    {
        return $this->provider->getServicePoints('GLS_PSS', ['parcel_shop'], 'DK', '8000', 'Aarhus', 'Street 1');
    }

    private static function servicePoint(string $id): object
    {
        return (object) [
            'id' => $id,
            'name' => 'Service point ' . $id,
            'address' => 'Street ' . $id,
            'address2' => null,
            'zipcode' => '8000',
            'city' => 'Aarhus',
            'country' => 'DK',
            'distance' => 100,
        ];
    }
}
