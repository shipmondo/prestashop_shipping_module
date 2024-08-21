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
            case 'read':
                $this->readServicePoint();
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

        $servicePoint = $repo->findOneBy(['id_cart' => $cart->id]);
        if (!$servicePoint) {
            $servicePoint = new ShipmondoServicePoint();
            $servicePoint->setCartId($cart->id);
        }

        $servicePoint
            ->setServicePointId(Tools::getValue('service_point_id'))
            ->setCarrierCode(Tools::getValue('carrier_code'))
            ->setName(Tools::getValue('name'))
            ->setAddress1(Tools::getValue('address1'))
            ->setAddress2(Tools::getValue('address2'))
            ->setZipCode(Tools::getValue('zip_code'))
            ->setCity(Tools::getValue('city'));

        $repo->persist($servicePoint);
        $repo->flush();

        $this->ajaxDie(json_encode(['success' => true, 'message' => 'Service point updated successfully']));
    }

    private function readServicePoint()
    {
        $id = Tools::getValue('id');
        $servicePoint = $this->getRepository()->find($id);

        $this->ajaxDie(json_encode(['success' => true, 'data' => $servicePoint]));
    }

    private function getExternalServicePointList()
    {
        $carrier_code       = Tools::getValue('carrier_code');
        $frontend_key       = Configuration::get('SHIPMONDO_FRONTEND_KEY');
        $frontend_type      = Configuration::get('SHIPMONDO_FRONTEND_TYPE');

        $cart = Context::getContext()->cart;
        $delivery_address = new Address($cart->id_address_delivery);

        $client = new GuzzleHttp\Client();
        $url = 'https://service-points.shipmondo.com/pickup-points.json';
        $response = $client->request('GET', $url, [
            'headers' => [
                'User-Agent' => 'Shipmondo Prestashop Module v' . $this->module->version,
            ],
            'query' => [
                'frontend_key'          => $frontend_key,
                'request_url'           => _PS_BASE_URL_,
                'request_version'       => _PS_VERSION_,
                'module_version'        => $this->module->version,
                'shipping_module_type'  => 'prestashop',
                'carrier_code'          => $carrier_code,
                'zipcode'               => $delivery_address->postcode,
                'country'               => Country::getIsoById($delivery_address->id_country),
                'address'               => $delivery_address->address1
            ]
        ]);

        $service_points = json_decode($response->getBody()->getContents());

        $carrier_logo_path = _MODULE_DIR_ . 'shipmondo/views/img/' . $carrier_code . '.png';
        if (!file_exists($carrier_logo_path)) {
            $carrier_logo_path = _MODULE_DIR_ . 'shipmondo/views/img/default.png';
        }

        $this->context->smarty->assign([
            'service_points' => $service_points,
            'selected_service_point_id' => 0,
            'carrier_code' => $carrier_code,
            'carrier_logo' => $carrier_logo_path,
            'service_points_json' => json_encode($service_points),
            'service_points_count' => 0,
        ]); 
        $html = $this->module->fetch('module:shipmondo/views/templates/front/' . Tools::strtolower($frontend_type) . '/content.tpl');
        $this->ajaxDie($html);

        $this->ajaxDie(json_encode(['success' => true, 'service_points_html' => $html]));
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