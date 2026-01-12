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
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
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

        $builder->add('carrier_id', ChoiceType::class, [
            'label' => $this->trans('Carrier', 'Admin.Global'),
            'required' => true,
            'choices' => $psCarriers,
        ]);

        $defaultFormValues = [];
        try {
            $defaultFormValues = $this->shipmondoCarrierHandler->getCarrierFormValues(null, null, null, null);
        } catch (\Exception $e) {
            // TODO: show an error?
        }

        $builder->add('carrier_code', ChoiceType::class, [
            'choices' => $defaultFormValues['choices']['carrier_code'] ?? [],
            'label' => $this->trans('Shipmondo carrier', 'Modules.Shipmondo.Admin'),
            'required' => true,
        ]);

        $builder->add('product_code', ChoiceType::class, [
            'choices' => $defaultFormValues['choices']['product_code'] ?? [],
            'label' => $this->trans('Product', 'Modules.Shipmondo.Admin'),
            'required' => true,
        ]);

        $builder->add('carrier_product_code', ChoiceType::class, [
            'choices' => $defaultFormValues['choices']['carrier_product_code'] ?? [],
            'invalid_message' => '',
            'label' => $this->trans('Carrier product', 'Modules.Shipmondo.Admin'),
            'required' => false,
        ]);

        $builder->add('service_point_types', ChoiceType::class, [
            'choices' => $defaultFormValues['choices']['service_point_types'] ?? [],
            'invalid_message' => '',
            'label' => $this->trans('Filter service point types', 'Modules.Shipmondo.Admin'),
            'multiple' => true,
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event) {
            $this->handlePreSetDataEvent($event);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $carrierCode = $data['carrier_code'];
            $productCode = $data['product_code'];
            $carrierProductCode = $data['carrier_product_code'];
            $servicePointTypes = $data['service_point_types'];

            $this->updateFormValues($form, $carrierCode, $productCode, $carrierProductCode, $servicePointTypes);
        });

        $builder->setAction($options['action']);
    }

    private function setCarrierCodeFormField(FormInterface $form, array $choices, ?string $value): void
    {
        $form->add('carrier_code', ChoiceType::class, [
            'choices' => $choices,
            'label' => $this->trans('Shipmondo carrier', 'Modules.Shipmondo.Admin'),
            'required' => true,
        ]);

        $form->get('carrier_code')->setData($value);
    }

    private function setProductCodeFormField(FormInterface $form, array $choices, ?string $value): void
    {
        $form->add('product_code', ChoiceType::class, [
            'choices' => $choices,
            'label' => $this->trans('Product', 'Modules.Shipmondo.Admin'),
            'required' => true,
        ]);

        $form->get('product_code')->setData($value);
    }

    private function setCarrierProductCodeFormField(FormInterface $form, array $choices, ?string $value, bool $required = false): void
    {
        $form->add('carrier_product_code', ChoiceType::class, [
            'choices' => $choices,
            'invalid_message' => '',
            'label' => $this->trans('Carrier product', 'Modules.Shipmondo.Admin'),
            'required' => $required,
        ]);

        $form->get('carrier_product_code')->setData($value);
    }

    private function setServicePointTypesFormField(FormInterface $form, array $choices, ?array $value): void
    {
        $form->add('service_point_types', ChoiceType::class, [
            'choices' => $choices,
            'invalid_message' => '',
            'label' => $this->trans('Filter service point types', 'Modules.Shipmondo.Admin'),
            'multiple' => true,
            'required' => false,
        ]);

        $form->get('service_point_types')->setData($value);
    }

    private function handleApiError(FormInterface $form, ShipmondoApiException $error): void
    {
        $form->addError(new FormError($this->trans(
            'An error occured when requesting Shipmondo: %apiError%',
            'Modules.Shipmondo.Admin',
            ['%apiError%' => $error->getMessage()]
        )));
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

    private function handlePreSetDataEvent(PreSetDataEvent $event): void
    {
        $carrier = $event->getData();
        $form = $event->getForm();

        if ($carrier instanceof ShipmondoCarrier) {
            $this->updateFormValues(
                $form,
                $carrier->getCarrierCode(),
                $carrier->getProductCode(),
                $carrier->getCarrierProductCode(),
                $carrier->getServicePointTypes()
            );
        }
    }

    private function updateFormValues(FormInterface $form, ?string $carrierCode, ?string $productCode, ?string $carrierProductCode, ?array $servicePointTypes): void
    {
        $formValues = [];

        try {
            $formValues = $this->shipmondoCarrierHandler->getCarrierFormValues(
                $carrierCode,
                $productCode,
                $carrierProductCode,
                $servicePointTypes
            );
        } catch (ShipmondoApiException $e) {
            $this->handleApiError($form, $e);
        }

        $this->setCarrierCodeFormField(
            $form,
            $formValues['choices']['carrier_code'] ?? [],
            $formValues['default']['carrier_code'] ?? null
        );

        $this->setProductCodeFormField(
            $form,
            $formValues['choices']['product_code'] ?? [],
            $formValues['default']['product_code'] ?? null
        );

        $this->setCarrierProductCodeFormField(
            $form,
            $formValues['choices']['carrier_product_code'] ?? [],
            $formValues['default']['carrier_product_code'] ?? null,
            ($formValues['default']['product_code'] ?? null) === 'service_point'
        );

        $this->setServicePointTypesFormField(
            $form,
            $formValues['choices']['service_point_types'] ?? [],
            $formValues['default']['service_point_types'] ?? null
        );
    }
}
