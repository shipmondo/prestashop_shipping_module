<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

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
            case 'getExternalList':
                $this->getExternalServicePointList();
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
        if(!$carrierId) {
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
                    $this->ajaxDie(json_encode(['status' => 'error', 'error' => $e->getMessage()])); // TODO show generic error message?
                }

                if (empty($externalServicePoints)) {
                    $this->ajaxDie(json_encode(['status' => 'error', 'error' => 'No service points found']));
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

                if(!$selectedServicePoint) {
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
            ]);
            
            $html = $this->module->fetch('module:shipmondo/views/templates/front/' . Configuration::get('SHIPMONDO_FRONTEND_TYPE') . '/selection_button.tpl');
        }

        $this->ajaxDie(json_encode(['status' => 'success', 'service_point_html' => $html]));
    }

    private function getExternalServicePointList(): void
    {
        $carrierCode = Tools::getValue('carrier_code');
        $frontendType = Configuration::get('SHIPMONDO_FRONTEND_TYPE');

        $carrierCode       = Tools::getValue('carrier_code');
        $lastCarrierCode  = Tools::getValue('last_carrier_code');
        $lastAddress       = (object) Tools::getValue('last_address');

        $cart = Context::getContext()->cart;
        $deliveryAddress = new Address($cart->id_address_delivery);

        // Check if reload of service point is needed
        $addressChanged = $this->hasAddressChanged($lastAddress, $deliveryAddress);
        if (!$addressChanged && $carrierCode == $lastCarrierCode) {
            $response = [
                'status' => 'success',
                'service_points_html' => '',
                'service_points' => [],
                'address_changed' => false
            ];

            $this->ajaxDie(json_encode($response));
        }

        $cart = Context::getContext()->cart;
        $deliveryAddress = new Address($cart->id_address_delivery);
        $servicePoint = $this->getRepository()->findOneBy(['cartId' => $cart->id, 'carrierCode' => $carrierCode]);
        $servicePointId = $servicePoint ? $servicePoint->getServicePointId() : 0;

        try {
            $servicePoints = $this->fetchExternalServicePoints($carrierCode, $deliveryAddress);
        } catch (ShipmondoApiException $e) {
            $this->ajaxDie(json_encode(['status' => 'error', 'error' => $e->getMessage()])); // TODO show generic error message?
        }

        $carrierLogoPath = 'shipmondo/views/img/' . $carrierCode . '.png';
        if (!file_exists(_PS_MODULE_DIR_ . $carrierLogoPath)) {
            $carrierLogoPath = 'shipmondo/views/img/pdk.png'; # TODO add default logo
        }

        $this->context->smarty->assign([
            'service_points' => $servicePoints,
            'selected_service_point_id' => $servicePointId,
            'carrier_code' => $carrierCode,
            'carrier_logo' => _MODULE_DIR_ . $carrierLogoPath,
            'service_points_json' => json_encode($servicePoints),
            'service_points_count' => count($servicePoints),
        ]);
        $html = $this->module->fetch('module:shipmondo/views/templates/front/' . Tools::strtolower($frontendType) . '/content.tpl');

        $this->ajaxDie(json_encode([
            'status' => 'success',
            'service_points_html' => $html,
            'service_points' => $servicePoints,
            'address_changed' => true,
            'new_address' => [
                'id_country' => $deliveryAddress->id_country,
                'address1' => $deliveryAddress->address1,
                'postcode' => $deliveryAddress->postcode,
            ]
        ]));
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
}