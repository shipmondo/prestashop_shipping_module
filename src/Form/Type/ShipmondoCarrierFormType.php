<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Form\Type;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use PrestaShopBundle\Translation\TranslatorInterface;
use Shipmondo\Entity\ShipmondoCarrier;
use Shipmondo\Exception\ShipmondoApiException;
use Shipmondo\ShipmondoCarrierHandler;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShipmondoCarrierFormType extends TranslatorAwareType
{
    /**
     * @var ShipmondoCarrierHandler
     */
    private $shipmondoCarrierHandler;

    public function __construct(TranslatorInterface $translator, array $locales, ShipmondoCarrierHandler $shipmondoCarrierHandler)
    {
        parent::__construct($translator, $locales);

        $this->shipmondoCarrierHandler = $shipmondoCarrierHandler;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allPsCarriers = \Carrier::getCarriers(\Context::getContext()->language->id, false, false, false, null, \Carrier::ALL_CARRIERS);
        $psCarriers = [$this->trans('Create new carrier', 'Modules.Shipmondo.Admin') => 0];
        foreach ($allPsCarriers as $carrier) {
            $psCarriers[$carrier['name']] = (int) $carrier['id_carrier'];
        }

        try {
            $allCarriers = $this->shipmondoCarrierHandler->getCarriers();
        } catch (\Exception $e) {
            $allCarriers = [];
        }

        $carriers = [];
        foreach ($allCarriers as $carrier) {
            $carriers[$carrier->name] = $carrier->code;
        }

        $builder
            ->add('carrier_id', ChoiceType::class, [
                'label' => $this->trans('Carrier', 'Admin.Global'),
                'required' => true,
                'choices' => $psCarriers,
            ])
            ->add('carrier_code', ChoiceType::class, [
                'label' => $this->trans('Shipmondo carrier', 'Modules.Shipmondo.Admin'),
                'required' => true,
                'choices' => $carriers,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $carrier = $event->getData();
            $form = $event->getForm();

            if ($carrier instanceof ShipmondoCarrier) {
                $form->add('product_code', ChoiceType::class, [
                    'label' => $this->trans('Product', 'Modules.Shipmondo.Admin'),
                    'required' => true,
                    'choices' => [$this->trans('Loading...', 'Modules.Shipmondo.Admin') => $carrier->getProductCode()], // Actual choices will be added in the POST_SUBMIT event
                ]);
            }
        });

        $handleFormEvent = function (FormEvent $event) {
            $carrierCode = (string) $event->getData();
            $form = $event->getForm();

            try {
                $products = $this->shipmondoCarrierHandler->getProducts($carrierCode);
            } catch (ShipmondoApiException $e) {
                $error = $this->trans('An error occured when requesting Shipmondo: %apiError%', 'Modules.Shipmondo.Admin', ['%apiError%' => $e->getMessage()]);
                $form->getParent()->addError(new FormError($error));
                $products = [];
            }

            $choices = [];
            foreach ($products as $product) {
                $choices[$product->name] = $product->code;
            }

            $form->getParent()->add('product_code', ChoiceType::class, [
                'label' => $this->trans('Product', 'Modules.Shipmondo.Admin'),
                'required' => true,
                'choices' => $choices,
            ]);
        };

        $builder->get('carrier_code')->addEventListener(FormEvents::POST_SET_DATA, $handleFormEvent);
        $builder->get('carrier_code')->addEventListener(FormEvents::POST_SUBMIT, $handleFormEvent);

        $builder->setAction($options['action']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShipmondoCarrier::class,
            'constraints' => [
                new UniqueEntity([
                    'fields' => 'carrierId',
                    'message' => $this->trans('The selected carrier is already associated with a Shipmondo carrier.', 'Modules.Shipmondo.Admin'),
                    'errorPath' => 'carrierId',
                ]),
            ],
        ]);
    }
}
