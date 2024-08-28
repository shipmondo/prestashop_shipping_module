<?php

namespace Shipmondo\Form\Type;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Carrier;
use Context;
use Shipmondo\Entity\ShipmondoCarrier;

class ShipmondoCarrierFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allPsCarriers = Carrier::getCarriers(Context::getContext()->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
        $psCarriers = [$this->trans('Create new carrier', 'Module.Shipmondo.Admin') => 0];
        foreach ($allPsCarriers as $carrier) {
            $psCarriers[$carrier['name']] = $carrier['id_carrier'];
        }

        $allCarriers = ShipmondoCarrier::getAvailableCarriers();
        $carriers = [];
        foreach ($allCarriers as $carrier) {
            $carriers[$carrier->name] = $carrier->code;
        }


        $builder
            ->add('carrier_id', ChoiceType::class, [
                'label' => $this->trans("Carrier", 'Module.Shipmondo.Admin'),
                'required' => true,
                'choices' => $psCarriers
            ])
            ->add('carrier_code', ChoiceType::class, [
                'label' => $this->trans("Shipmondo Carrier", 'Module.Shipmondo.Admin'),
                'required' => true,
                'choices' => $carriers
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $carrier = $event->getData();
            $form = $event->getForm();

            if ($carrier instanceof ShipmondoCarrier) {
                $form->add('product_code', ChoiceType::class, [
                    'label' => $this->trans("Product", 'Module.Shipmondo.Admin'),
                    'required' => true,
                    'choices' => ['Loading...' => $carrier->getProductCode()] // Actual choices will be added in the POST_SUBMIT event
                ]);
            }
        });

        $builder->get('carrier_code')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $carrierCode = $event->getData();
            $form = $event->getForm();

            $products = ShipmondoCarrier::getAvailableProducts($carrierCode);

            $choices = [];
            foreach ($products as $product) {
                $choices[$product->name] = $product->code;
            }

            $form->getParent()->add('product_code', ChoiceType::class, [
                'label' => $this->trans("Product", 'Module.Shipmondo.Admin'),
                'required' => true,
                'choices' => $choices
            ]);
        });

        $builder->setAction($options['action']);
    }
}