<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Form\Type;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ShipmondoConfigurationFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('frontend_key', TextType::class, [
                'label' => $this->trans('Delivery Checkout key', 'Modules.Shipmondo.Admin'),
                'required' => true,
            ])
            ->add('frontend_type', ChoiceType::class, [
                'label' => $this->trans('Show service points in', 'Modules.Shipmondo.Admin'),
                'required' => true,
                'choices' => [
                    $this->trans('Modal', 'Modules.Shipmondo.Admin') => 'popup',
                    $this->trans('Dropdown', 'Modules.Shipmondo.Admin') => 'dropdown',
                ],
            ])
            ->add('google_api_key', TextType::class, [
                'label' => $this->trans('Google Maps API key', 'Modules.Shipmondo.Admin'),
                'help' => $this->trans('Only used when displaying service points in modal.', 'Modules.Shipmondo.Admin'),
                'required' => false,
            ]);
    }
}
