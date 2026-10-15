<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Tests\Support;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;

/**
 * Array backed configuration, replacing ps_configuration in unit tests.
 */
final class InMemoryConfiguration implements ConfigurationInterface
{
    /**
     * @var array<string, mixed>
     */
    private $values;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function get($key, $default = null, $shopConstraint = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function set($key, $value, $shopConstraint = null)
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function has($key, $shopConstraint = null)
    {
        return array_key_exists($key, $this->values);
    }

    public function remove($key)
    {
        unset($this->values[$key]);

        return $this;
    }
}
