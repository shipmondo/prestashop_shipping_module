<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shipmondo\ApiClient;

final class ApiClientTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv(ApiClient::API_URL_ENV_VAR);
    }

    public function testUsesShipmondoApiByDefault(): void
    {
        putenv(ApiClient::API_URL_ENV_VAR);

        self::assertSame('https://app.shipmondo.com/api/public/v3/', ApiClient::resolveBaseUri());
    }

    public function testIgnoresBlankOverride(): void
    {
        putenv(ApiClient::API_URL_ENV_VAR . '=  ');

        self::assertSame('https://app.shipmondo.com/api/public/v3/', ApiClient::resolveBaseUri());
    }

    #[DataProvider('overrides')]
    public function testUsesOverrideFromEnvironment(string $override, string $expected): void
    {
        putenv(ApiClient::API_URL_ENV_VAR . '=' . $override);

        self::assertSame($expected, ApiClient::resolveBaseUri());
    }

    public static function overrides(): array
    {
        return [
            'without trailing slash' => ['http://shipmondo-mock:8080/api/public/v3', 'http://shipmondo-mock:8080/api/public/v3/'],
            'with trailing slash' => ['http://shipmondo-mock:8080/api/public/v3/', 'http://shipmondo-mock:8080/api/public/v3/'],
            'with surrounding whitespace' => [' http://localhost:8080 ', 'http://localhost:8080/'],
        ];
    }
}
