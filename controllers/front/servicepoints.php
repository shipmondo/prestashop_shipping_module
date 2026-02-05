<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Shipmondo\Entity\ShipmondoServicePoint;
use Shipmondo\Exception\ShipmondoApiException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ShipmondoServicepointsModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool
     */
    public $ajax = true;

    public function initContent(): void
    {
        parent::initContent();

        try {
            $action = Tools::getValue('action');

            switch ($action) {
                case 'get':
                    $response = $this->getServicePoint();
                    $response->send();
                    exit;
                case 'update':
                    $response = $this->updateServicePoint();
                    $response->send();
                    exit;
                default:
                    $response = $this->invalidAction();
                    $response->send();
                    exit;
            }
        } catch (Exception $e) {
            $errorMessage = $this->trans('An unknown error occurred.', [], 'Modules.Shipmondo.Front');

            $response = new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage(),
                'html' => $this->getErrorHtml($errorMessage),
            ]);
            $response->send();
            exit;
        }
    }

    private function updateServicePoint(): JsonResponse
    {
        $cart = Context::getContext()->cart;
        $repo = $this->getServicePointRepository();

        $servicePoint = $repo->findOneBy(['cartId' => $cart->id]);

        if (!$servicePoint) {
            $servicePoint = new ShipmondoServicePoint();
            $servicePoint->setCartId($cart->id);
        }

        $deliveryAddress = new Address($cart->id_address_delivery);
        $countryCode = Country::getIsoById($deliveryAddress->id_country);
        $address2 = Tools::getValue('address2');

        $servicePoint
            ->setServicePointId(Tools::getValue('service_point_id'))
            ->setCarrierCode(Tools::getValue('carrier_code'))
            ->setName(Tools::getValue('name'))
            ->setAddress1(Tools::getValue('address1'))
            ->setAddress2($address2 ? $address2 : null)
            ->setZipCode(Tools::getValue('zip_code'))
            ->setCity(Tools::getValue('city'))
            ->setCountryCode($countryCode);

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->persist($servicePoint);
        $entityManager->flush();

        $this->context->smarty->assign([
            'carrier' => new Carrier($cart->id_carrier),
            'servicePoint' => (object) [ // Imitate response from external service point API
                'id' => $servicePoint->getServicePointId(),
                'name' => $servicePoint->getName(),
                'address' => $servicePoint->getAddress1(),
                'address2' => $servicePoint->getAddress2(),
                'zipcode' => $servicePoint->getZipCode(),
                'city' => $servicePoint->getCity(),
                'country_code' => $servicePoint->getCountryCode(),
                'distance' => Tools::getValue('distance'),
            ],
        ]);
        $html = $this->module->fetch('module:shipmondo/views/templates/front/_partials/selected_service_point.tpl');

        return new JsonResponse(['status' => 'success', 'html' => $html]);
    }

    private function getServicePoint(): JsonResponse
    {
        $cart = Context::getContext()->cart;
        $repo = $this->getServicePointRepository();

        $carrierId = Tools::getValue('carrier_id');
        if (!$carrierId) {
            $carrierId = $cart->id_carrier;
        }

        $carrier = $this->getCarrierRepository()->findOneBy(['carrierId' => $carrierId]);

        $html = '';

        if ($carrier && $carrier->getProductCode() === 'service_point') {
            $servicePoint = $repo->findOneBy(['cartId' => $cart->id]);
            $externalServicePoints = [];
            $selectedServicePoint = null;

            // Find and set the nearest service point
            $deliveryAddress = new Address($cart->id_address_delivery);

            if ($deliveryAddress) {
                try {
                    $externalServicePoints = $this->fetchExternalServicePoints(
                        $carrier->getCarrierProductCode(),
                        $carrier->getServicePointTypes(),
                        $deliveryAddress
                    );
                } catch (ShipmondoApiException $e) {
                    $errorMessage = $this->trans('An error occurred while fetching service points.', [], 'Modules.Shipmondo.Front');
                    $errorHtml = $this->getErrorHtml($errorMessage);

                    return new JsonResponse(['status' => 'error', 'error' => $e->getMessage(), 'html' => $errorHtml]);
                }

                if (empty($externalServicePoints)) {
                    $errorMessage = $this->trans('No service points found for the given address.', [], 'Modules.Shipmondo.Front');
                    $errorHtml = $this->getErrorHtml($errorMessage);

                    return new JsonResponse(['status' => 'error', 'error' => $errorMessage, 'html' => $errorHtml]);
                }

                if ($servicePoint) {
                    foreach ($externalServicePoints as $externalServicePoint) {
                        if ($servicePoint && $servicePoint->getServicePointId() == $externalServicePoint->id) {
                            $selectedServicePoint = $externalServicePoint;
                            break;
                        }
                    }
                } else {
                    $servicePoint = new ShipmondoServicePoint();
                    $servicePoint->setCartId($cart->id);
                }

                if (!$selectedServicePoint) {
                    $selectedServicePoint = $externalServicePoints[0];
                }

                $servicePoint->setCarrierCode($carrier->getCarrierCode());
                $servicePoint->setServicePointId($selectedServicePoint->id);
                $servicePoint->setName($selectedServicePoint->name);
                $servicePoint->setAddress1($selectedServicePoint->address);
                $servicePoint->setAddress2($selectedServicePoint->address2);
                $servicePoint->setZipCode($selectedServicePoint->zipcode);
                $servicePoint->setCity($selectedServicePoint->city);
                $servicePoint->setCountryCode($selectedServicePoint->country);

                $entityManager = $this->get('doctrine.orm.entity_manager');
                $entityManager->persist($servicePoint);
                $entityManager->flush();
            }

            $this->context->smarty->assign([
                'carrier' => new Carrier($carrierId),
                'selectedServicePoint' => $selectedServicePoint,
                'servicePoints' => $externalServicePoints,
                'frontendType' => Configuration::get('SHIPMONDO_FRONTEND_TYPE'),
            ]);

            $html = $this->module->fetch('module:shipmondo/views/templates/front/service_points_selector.tpl');
        }

        return new JsonResponse(['status' => 'success', 'html' => $html]);
    }

    private function invalidAction(): JsonResponse
    {
        return new JsonResponse(['status' => 'error', 'error' => 'Invalid action']);
    }

    private function getCarrierRepository()
    {
        /**
         * @var Shipmondo\Repository\ShipmondoCarrierRepository<Shipmondo\Entity\ShipmondoCarrier>
         */
        $repo = $this->get('shipmondo.repository.shipmondo_carrier');

        return $repo;
    }

    private function getServicePointRepository()
    {
        /**
         * @var Shipmondo\Repository\ShipmondoServicePointRepository<ShipmondoServicePoint>
         */
        $repo = $this->module->get('shipmondo.repository.shipmondo_service_point');

        return $repo;
    }

    private function getApiClient()
    {
        /**
         * @var Shipmondo\ApiClient
         */
        $client = $this->container->get('shipmondo.api_client');

        return $client;
    }

    private function fetchExternalServicePoints(string $carrierProductCode, ?array $servicePointTypes, Address $deliveryAddress): array
    {
        $countryCode = Country::getIsoById($deliveryAddress->id_country);

        if (is_bool($countryCode)) {
            // NOTE: should this raise instead?
            $countryCode = '';
        }

        $zipcode = $deliveryAddress->postcode ?? '';

        $city = $deliveryAddress->city ?? '';

        $address = $deliveryAddress->address1 ?? '';

        return $this->getApiClient()->getServicePoints(
            $carrierProductCode,
            $servicePointTypes,
            $countryCode,
            $zipcode,
            $city,
            $address
        );
    }

    private function getErrorHtml(string $errorMessage): string
    {
        $this->context->smarty->assign([
            'errorMessage' => $errorMessage,
        ]);

        return $this->module->fetch('module:shipmondo/views/templates/front/_partials/error.tpl');
    }
}
