<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo;

use Shipmondo\Cache\FileCache;
use Shipmondo\Exception\ShipmondoApiException;

/**
 * Looks up service points near an address, caching successful non-empty responses
 * so repeated checkout views do not call the Shipmondo API every time.
 */
class ServicePointProvider
{
    /**
     * Number of seconds a service point lookup is cached.
     */
    public const CACHE_TTL = 300;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var FileCache
     */
    private $cache;

    public function __construct(ApiClient $apiClient, FileCache $cache)
    {
        $this->apiClient = $apiClient;
        $this->cache = $cache;
    }

    /**
     * Get service points for a carrier product near an address.
     *
     * @throws ShipmondoApiException
     */
    public function getServicePoints(string $productCode, ?array $servicePointTypes, string $countryCode, string $zipcode, string $city, string $address): array
    {
        $cacheKey = $this->getCacheKey($productCode, $servicePointTypes, $countryCode, $zipcode, $city, $address);

        $cachedServicePoints = $this->cache->get($cacheKey);

        if ($cachedServicePoints !== null) {
            $servicePoints = json_decode($cachedServicePoints, false);

            if (is_array($servicePoints) && count($servicePoints) > 0) {
                return $servicePoints;
            }
        }

        $servicePoints = $this->apiClient->getServicePoints($productCode, $servicePointTypes, $countryCode, $zipcode, $city, $address);

        // Only cache usable results, so an empty response or a failed request is retried on the next lookup.
        if (count($servicePoints) > 0) {
            $encodedServicePoints = json_encode($servicePoints);

            if ($encodedServicePoints !== false) {
                $this->cache->set($cacheKey, $encodedServicePoints, self::CACHE_TTL);
            }
        }

        return $servicePoints;
    }

    /**
     * Build a cache key from everything the request depends on, normalized the same way as ApiClient::getServicePoints().
     */
    private function getCacheKey(string $productCode, ?array $servicePointTypes, string $countryCode, string $zipcode, string $city, string $address): string
    {
        return serialize([
            'base_uri' => $this->apiClient->getBaseUri(),
            'frontend_key' => $this->apiClient->getFrontendKey(),
            'product_code' => $productCode,
            'service_point_types' => array_values(array_map('strval', $servicePointTypes ?? [])),
            'country_code' => trim($countryCode),
            'zipcode' => trim($zipcode),
            'city' => trim($city),
            'address' => trim($address),
        ]);
    }
}
