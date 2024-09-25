<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo\Form\Type;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ShipmondoConfigurationFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $frontendKeyHelp = $this->trans('Insert your shipping module API key here. You can generate a key from', 'Modules.Shipmondo.Admin');
        $frontendKeyHelp .= ' <a target="_blank" href="https://app.shipmondo.com/main/app/#/setting/freight-module">Shipmondo</a>.';

        $googleApiKeyHelp = $this->trans('Insert your Google API key here. You can generate a key from', 'Modules.Shipmondo.Admin');
        $googleApiKeyHelp .= ' <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">Google</a>.';

        $builder
            ->add('frontend_key', TextType::class, [
                'label' => $this->trans('Shipping module key', 'Modules.Shipmondo.Admin'),
                'help' => $frontendKeyHelp,
                'help_html' => true,
                'required' => true,
            ])
            ->add('google_api_key', TextType::class, [
                'label' => $this->trans('Google Maps API key', 'Modules.Shipmondo.Admin'),
                'help' => $googleApiKeyHelp,
                'help_html' => true,
                'required' => false,
            ])
            ->add('frontend_type', ChoiceType::class, [
                'label' => $this->trans('Show service points in', 'Modules.Shipmondo.Admin'),
                'required' => true,
                'choices' => [
                    $this->trans('Modal', 'Modules.Shipmondo.Admin') => 'popup',
                    $this->trans('Dropdown', 'Modules.Shipmondo.Admin') => 'dropdown',
                ]
            ])
        ;
    }
}