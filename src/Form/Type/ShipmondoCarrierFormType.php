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
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->handlePreSetData($event);
        });

        $handleFormEvent = function (FormEvent $event, $data) {
            $carrierCode = (string) $event->getData();
            $form = $event->getForm();

            /**
             * @var ShipmondoCarrier
             */
            $carrier = $form->getParent()->getData();

            $form->getParent()->addError(new FormError('handleFormEvent' . time() . ': ' . json_encode([
                'carrier_code' => $carrierCode,
                'carrier_code_carrier' => $carrier->getCarrierCode(),
                'carrier_code_form' => $form->getParent()->get('carrier_code')->getData(),
                'product_code' => $carrier->getProductCode(),
                'carrier_product_code' => $carrier->getCarrierProductCode(),
                'service_point_types' => $carrier->getServicePointTypes(),

                'data' => $data,
            ])));

            $products = [];
            try {
                $products = $this->shipmondoCarrierHandler->getProducts($carrierCode);
            } catch (ShipmondoApiException $e) {
                $this->handleApiError($form->getParent(), $e);
            }

            $choices = [];
            foreach ($products as $product) {
                $choices[$product->name] = $product->code;
            }

            $this->setProductCodeFormField($form->getParent(), $choices);

            if ($form->getParent()->get('product_code')->getData() === 'service_point') {
                $carrierProductChoices = [];

                $currentCarrierProductCode = null;
                $found = false;

                try {
                    $carrierProductChoices = self::extractCarrierProductChoices($this->shipmondoCarrierHandler->getCarrierProducts(
                        $form->getParent()->get('carrier_code')->getData()
                    ));

                    if ($form->getParent()->has('carrier_product_code')) {
                        $currentCarrierProductCode = $form->getParent()->get('carrier_product_code')->getData() ?? null;

                        $fallback = null;

                        foreach ($carrierProductChoices as $code) {
                            if ($currentCarrierProductCode === $code) {
                                $found = true;
                                break;
                            }

                            if ($fallback === null) {
                                $fallback = $code;
                            }
                        }

                        if (!$found) {
                            $currentCarrierProductCode = $fallback;
                        }
                    }
                } catch (ShipmondoApiException $e) {
                    $this->handleApiError($form->getParent(), $e);
                }

                if (!$found && $form->getParent()->has('carrier_product_code')) {
                    $form->getParent()->get('carrier_product_code')->setData($currentCarrierProductCode);
                }

                $this->setCarrierProductCodeFormField($form->getParent(), $carrierProductChoices);

                $servicePointTypeChoices = [];

                try {
                    $servicePointTypeChoices = self::extractServicePointTypeChoices($this->shipmondoCarrierHandler->getServicePointTypes(
                        $currentCarrierProductCode
                    ));
                } catch (ShipmondoApiException $e) {
                    $this->handleApiError($form->getParent(), $e);
                }

                if (count($servicePointTypeChoices) > 0) {
                    $this->setServicePointTypesFormField($form->getParent(), $servicePointTypeChoices);

                    if (!$found) {
                        $form->getParent()->get('service_point_types')->setData([]);
                    }
                } elseif ($form->getParent()->has('service_point_types')) {
                    $form->getParent()->get('service_point_types')->setData([]);
                    $form->getParent()->remove('service_point_types');
                }
            } else {
                if ($form->getParent()->has('carrier_product_code')) {
                    $form->getParent()->get('carrier_product_code')->setData(null);
                    $form->getParent()->remove('carrier_product_code');
                }

                if ($form->getParent()->has('service_point_types')) {
                    $form->getParent()->get('service_point_types')->setData([]);
                    $form->getParent()->remove('service_point_types');
                }
            }
        };

        $builder->get('carrier_code')->addEventListener(FormEvents::POST_SET_DATA, $handleFormEvent);
        $builder->get('carrier_code')->addEventListener(FormEvents::POST_SUBMIT, $handleFormEvent);

        $hello = function (FormEvent $event, $data) {
            $event->getForm()->addError(new FormError('EH ' . time() . ' : ' . json_encode([
                'data' => $data,
                'data_type' => gettype($data),
                'debug_type' => get_debug_type($data),
            ])));
        };

        $builder->addEventListener(FormEvents::POST_SET_DATA, $hello);
        $builder->addEventListener(FormEvents::POST_SUBMIT, $hello);

        $builder->setAction($options['action']);
    }

    private function setProductCodeFormField(FormInterface $form, array $choices): void
    {
        $form->add('product_code', ChoiceType::class, [
            'label' => $this->trans('Product', 'Modules.Shipmondo.Admin'),
            'required' => true,
            'choices' => $choices,
        ]);
    }

    private function setCarrierProductCodeFormField(FormInterface $form, array $choices): void
    {
        $form->add('carrier_product_code', ChoiceType::class, [
            'label' => $this->trans('Carrier Product', 'Modules.Shipmondo.Admin'),
            'choices' => $choices,
            'invalid_message' => '',
            'required' => true,
        ]);
    }

    private function setServicePointTypesFormField(FormInterface $form, array $choices): void
    {
        $form->add('service_point_types', ChoiceType::class, [
            'label' => $this->trans('Filter Service Point Types', 'Modules.Shipmondo.Admin'),
            'choices' => $choices,
            'invalid_message' => '',
            'multiple' => true,
            'required' => false,
        ]);
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

    private static function extractCarrierProductChoices(array $carrierProducts): array
    {
        $choices = [];

        foreach ($carrierProducts as $product) {
            if (!isset($product->service_point_product) || $product->service_point_product !== true) {
                continue;
            }

            $choices[$product->name] = $product->product_code;
        }

        return $choices;
    }

    private static function extractServicePointTypeChoices(array $servicePointTypes): array
    {
        $choices = [];

        foreach ($servicePointTypes as $servicePointType) {
            $choices[$servicePointType->name] = $servicePointType->code;
        }

        return $choices;
    }

    private function handlePreSetData(FormEvent $event): void
    {
        $carrier = $event->getData();
        $form = $event->getForm();

        if ($carrier instanceof ShipmondoCarrier) {
            $this->setProductCodeFormField($form, [
                $this->trans('Loading...', 'Modules.Shipmondo.Admin') => $carrier->getProductCode(),
            ]);

            if ($carrier->getProductCode() === 'service_point') {
                $this->setCarrierProductCodeFormField($form, [
                    $this->trans('Loading...', 'Modules.Shipmondo.Admin') => $carrier->getCarrierProductCode(),
                ]);

                if ($carrier->getCarrierProductCode() !== null) {
                    $servicePointChoices = [];

                    try {
                        $servicePointChoices = self::extractServicePointTypeChoices($this->shipmondoCarrierHandler->getServicePointTypes($carrier->getCarrierProductCode()));
                    } catch (ShipmondoApiException $e) {
                        $this->handleApiError($form, $e);
                    }

                    $this->setServicePointTypesFormField($form, $servicePointChoices);
                }
            }
        }
    }
}
