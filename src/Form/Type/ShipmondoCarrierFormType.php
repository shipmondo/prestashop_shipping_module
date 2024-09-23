<?php
/**
 *  @author    Shipmondo
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

namespace Shipmondo\Form\Type;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use PrestaShopBundle\Translation\TranslatorInterface;
use Carrier;
use Context;
use Shipmondo\Exception\ShipmondoApiException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Shipmondo\ShipmondoCarrierHandler;
use Shipmondo\Entity\ShipmondoCarrier;

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
        $allPsCarriers = Carrier::getCarriers(Context::getContext()->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
        $psCarriers = [$this->trans('Create new carrier', 'Module.Shipmondo.Admin') => 0];
        foreach ($allPsCarriers as $carrier) {
            $psCarriers[$carrier['name']] = $carrier['id_carrier'];
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
                    'choices' => [$this->trans('Loading...', 'Module.Shipmondo.Admin') => $carrier->getProductCode()] // Actual choices will be added in the POST_SUBMIT event
                ]);
            }
        });

        $handleFormEvent = function (FormEvent $event) {
            $carrierCode = (string) $event->getData();
            $form = $event->getForm();

            try {
                $products = $this->shipmondoCarrierHandler->getProducts($carrierCode);
            } catch (ShipmondoApiException $e) {
                $error = $this->trans("An error occured when requesting Shipmondo: %apiError%", 'Module.Shipmondo.Admin', ['%apiError%' => $e->getMessage()]);
                $form->getParent()->addError(new FormError($error));
                $products = [];
            }

            $choices = [];
            foreach ($products as $product) {
                $choices[$product->name] = $product->code;
            }

            $form->getParent()->add('product_code', ChoiceType::class, [
                'label' => $this->trans("Product", 'Module.Shipmondo.Admin'),
                'required' => true,
                'choices' => $choices
            ]);
        };

        $builder->get('carrier_code')->addEventListener(FormEvents::POST_SET_DATA, $handleFormEvent);
        $builder->get('carrier_code')->addEventListener(FormEvents::POST_SUBMIT, $handleFormEvent);

        $builder->setAction($options['action']);
    }
}