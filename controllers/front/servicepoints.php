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
use Doctrine\ORM\EntityRepository;

class ShipmondoServicepointsModuleFrontController extends ModuleFrontController
{

    public $ajax = true;

    public function initContent(): void
    {
        parent::initContent();

        $action = Tools::getValue('action');
        switch ($action) {
            case 'get':
                $this->getServicePoint();
                break;
            case 'update':
                $this->updateServicePoint();
                break;
            default:
                $this->invalidAction();
                break;
        }
    }

    private function updateServicePoint(): void
    {
        $cart = Context::getContext()->cart;
        $repo = $this->getRepository();

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
            'service_point' => (object) [
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


        $this->ajaxDie(json_encode(['status' => 'success', 'selected_service_point_html' => $html]));
    }

    private function getServicePoint(): void
    {
        $cart = Context::getContext()->cart;
        $repo = $this->getRepository();

        $carrierId = Tools::getValue('carrier_id');
        if (!$carrierId) {
            $carrierId = $cart->id_carrier;
        }

        $carrier = $this->get('shipmondo.repository.shipmondo_carrier')->findOneBy(['carrierId' => $carrierId]);

        $html = '';
        if ($carrier && $carrier->getProductCode() === 'service_point') {
            $servicePoint = $repo->findOneBy(['cartId' => $cart->id]);
            $externalServicePoints = [];
            $selectedServicePoint = null;

            // Find and set the nearest service point
            $deliveryAddress = new Address($cart->id_address_delivery);

            if ($deliveryAddress) {
                try {
                    $externalServicePoints = $this->fetchExternalServicePoints($carrier->getCarrierCode(), $deliveryAddress);
                } catch (ShipmondoApiException $e) {
                    $errorMessage = $this->trans('An error occured while fetching service points.', [], 'Modules.Shipmondo.Front');
                    $errorHtml = $this->getErrorHtml($errorMessage);
                    $this->ajaxDie(json_encode(['status' => 'error', 'error' => $e->getMessage(), 'error_html' => $errorHtml]));
                }

                if (empty($externalServicePoints)) {
                    $errorMessage = $this->trans('No service points found for the given address.', [], 'Modules.Shipmondo.Front');
                    $errorHtml = $this->getErrorHtml($errorMessage);
                    $this->ajaxDie(json_encode(['status' => 'error', 'error' => $errorMessage, 'error_html' => $errorHtml]));
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
                $servicePoint->setCountryCode($selectedServicePoint->country_code);

                $entityManager = $this->get('doctrine.orm.entity_manager');
                $entityManager->persist($servicePoint);
                $entityManager->flush();
            }

            $this->context->smarty->assign([
                'carrier' => new Carrier($carrierId),
                'service_point' => $selectedServicePoint,
                'service_points' => $externalServicePoints,
                'frontendType' => Configuration::get('SHIPMONDO_FRONTEND_TYPE')
            ]);

            $html = $this->module->fetch('module:shipmondo/views/templates/front/service_points_selector.tpl');
        }

        $this->ajaxDie(json_encode(['status' => 'success', 'service_point_html' => $html]));
    }

    private function invalidAction(): void
    {
        $this->ajaxDie(json_encode(['status' => 'error', 'error' => 'Invalid action']));
    }

    private function getRepository(): EntityRepository
    {
        return $this->module->get('shipmondo.repository.shipmondo_service_point');
    }

    private function hasAddressChanged(object $oldAddress, Address $newAddress): bool
    {
        return !empty($oldAddress)
            && property_exists($oldAddress, 'id_country')
            && property_exists($oldAddress, 'postcode')
            && property_exists($oldAddress, 'address1')
            && ($oldAddress->id_country != $newAddress->id_country
                || $oldAddress->postcode != $newAddress->postcode
                || $oldAddress->address1 != $newAddress->address1);
    }

    private function fetchExternalServicePoints(string $carrierCode, Address $deliveryAddress): array
    {
        return $this->container->get('shipmondo.api_client')->getServicePoints([
            'request_url' => _PS_BASE_URL_,
            'request_version' => _PS_VERSION_,
            'module_version' => $this->module->version,
            'shipping_module_type' => 'prestashop',
            'carrier_code' => $carrierCode,
            'zipcode' => $deliveryAddress->postcode,
            'country' => Country::getIsoById($deliveryAddress->id_country),
            'address' => $deliveryAddress->address1
        ]);
    }

    private function getErrorHtml(string $errorMessage): string
    {
        $this->context->smarty->assign([
            'errorMessage' => $errorMessage
        ]);

        return $this->module->fetch('module:shipmondo/views/templates/front/_partials/error.tpl');
    }
}