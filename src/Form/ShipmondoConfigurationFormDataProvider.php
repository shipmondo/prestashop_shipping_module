<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

/**
 * Provider is responsible for providing form data, in this case, it is returned from the configuration component.
 *
 * Class ShipmondoConfigurationFormDataProvider
 */
class ShipmondoConfigurationFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var DataConfigurationInterface
     */
    private $shipmondoConfigurationDataConfiguration;

    public function __construct(DataConfigurationInterface $shipmondoConfigurationDataConfiguration)
    {
        $this->shipmondoConfigurationDataConfiguration = $shipmondoConfigurationDataConfiguration;
    }

    public function getData(): array
    {
        return $this->shipmondoConfigurationDataConfiguration->getConfiguration();
    }

    public function setData(array $data): array
    {
        return $this->shipmondoConfigurationDataConfiguration->updateConfiguration($data);
    }
}