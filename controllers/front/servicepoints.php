<?php

use Shipmondo\Entity\ShipmondoServicePoint;

class ShipmondoServicepointsModuleFrontController extends ModuleFrontController
{

    public $ajax = true;

    public function initContent()
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

    private function updateServicePoint()
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

        $this->ajaxDie(json_encode(['success' => true, 'service_point' => $servicePoint->toArray()]));
    }

    private function getServicePoint()
    {
        $cart = Context::getContext()->cart;
        $repo = $this->getRepository();

        $servicePoint = $repo->findOneBy(['cartId' => $cart->id]);
        if (!$servicePoint) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Service point not found for cart']));
        }

        $this->ajaxDie(json_encode(['success' => true, 'data' => $servicePoint]));
    }

    private function getExternalServicePointList()
    {
        $carrierCode = Tools::getValue('carrier_code');
        $frontendType = Configuration::get('SHIPMONDO_FRONTEND_TYPE');

        $client = $this->container->get('shipmondo.api_client');

        $cart = Context::getContext()->cart;
        $deliveryAddress = new Address($cart->id_address_delivery);
        $servicePoint = $this->getRepository()->findOneBy(['cartId' => $cart->id]);
        $servicePointId = $servicePoint ? $servicePoint->getServicePointId() : 0;
        
        $servicePoints = $client->getServicePoints([
            'request_url' => _PS_BASE_URL_,
            'request_version' => _PS_VERSION_,
            'module_version' => $this->module->version,
            'shipping_module_type' => 'prestashop',
            'carrier_code' => $carrierCode,
            'zipcode' => $deliveryAddress->postcode,
            'country' => Country::getIsoById($deliveryAddress->id_country),
            'address' => $deliveryAddress->address1
        ]);
        
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
            'success' => true,
            'service_points_html' => $html,
            'service_points' => $servicePoints,
            'address_changed' => true
        ]));
    }

    private function invalidAction()
    {
        $this->ajaxDie(json_encode(['success' => false, 'message' => 'Invalid action']));
    }

    private function getRepository()
    {
        return $this->module->get('doctrine')->getRepository(ShipmondoServicePoint::class);
    }
}