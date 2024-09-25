<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use PrestaShopBundle\Translation\TranslatorInterface;

/**
 * Configuration is used to save data to configuration table and retrieve from it.
 */
final class ShipmondoConfigurationDataConfiguration implements DataConfigurationInterface
{
    public const SHIPMONDO_FRONTEND_KEY = 'SHIPMONDO_FRONTEND_KEY';
    public const SHIPMONDO_GOOGLE_API_KEY = 'SHIPMONDO_GOOGLE_API_KEY';
    public const SHIPMONDO_FRONTEND_TYPE = 'SHIPMONDO_FRONTEND_TYPE';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ConfigurationInterface $configuration, TranslatorInterface $translator)
    {
        $this->configuration = $configuration;
        $this->translator = $translator;
    }

    public function getConfiguration(): array
    {
        return [
            'frontend_key' => $this->configuration->get(static::SHIPMONDO_FRONTEND_KEY),
            'google_api_key' => $this->configuration->get(static::SHIPMONDO_GOOGLE_API_KEY),
            'frontend_type' => $this->configuration->get(static::SHIPMONDO_FRONTEND_TYPE),
        ];
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        try {
            if ($this->validateConfiguration($configuration)) {
                $this->configuration->set(static::SHIPMONDO_FRONTEND_KEY, $configuration['frontend_key']);
                $this->configuration->set(static::SHIPMONDO_GOOGLE_API_KEY, $configuration['google_api_key']);
                $this->configuration->set(static::SHIPMONDO_FRONTEND_TYPE, $configuration['frontend_type']);
            }
        } catch (\InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        return $errors;
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @return bool
     */
    public function validateConfiguration(array $configuration): bool
    {
        if (empty($configuration['frontend_key'])) {
            throw new \InvalidArgumentException($this->trans('Shipping module key is required', 'Modules.Shipmondo.Admin'));
        }

        $frontendType = $configuration['frontend_type'];
        if (empty($frontendType)) {
            throw new \InvalidArgumentException($this->trans('Display type is required', 'Modules.Shipmondo.Admin'));
        } else if (!in_array($frontendType, ['popup', 'dropdown'])) {
            throw new \InvalidArgumentException($this->trans('Invalid display type', 'Modules.Shipmondo.Admin'));
        }

        if ($frontendType === "popup" && empty($configuration['google_api_key'])) {
            throw new \InvalidArgumentException($this->trans('Google API Key is required when using modal display type', 'Modules.Shipmondo.Admin'));
        }

        return true;
    }

    /**
     * Translate
     * 
     * @return string
     */
    private function trans(string $key, string $domain, array $parameters = []): string
    {
        return $this->translator->trans($key, $parameters, $domain);
    }
}