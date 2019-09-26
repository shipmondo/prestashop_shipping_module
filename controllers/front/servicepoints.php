<?php
/**
 *  @author    Shipmondo
 *  @copyright 2019 Shipmondo
 *  @license   All rights reserved
 */

class ShipmondoServicepointsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $response = [];
        switch (Tools::getValue('method')) {
            case 'get_list':
                $cart = Context::getContext()->cart;

                $sql = new DbQuery();
                $sql
                    ->select('service_point')
                    ->from('shipmondo_selected_service_points')
                    ->where('id_cart = ' . (int) $cart->id);
                $result = Db::getInstance()->getRow($sql);

                $service_point_id = null;
                if ($result) {
                    $service_point_id = Tools::jsonDecode($result['service_point'])->address2;
                    $service_point_id = preg_replace('/\D/', '', $service_point_id);
                }

                $shipping_agent = Tools::getValue('shipping_agent');
                $frontend_key = Configuration::get('SHIPMONDO_FRONTEND_KEY');

                $delivery_address = new Address($cart->id_address_delivery);
                
                $sql = new DbQuery();
                $sql
                    ->select('iso_code')
                    ->from('country')
                    ->where('id_country = "' . pSQL($delivery_address->id_country) . '"');
                $country_result = Db::getInstance()->getRow($sql);

                $country_code = 'DK';
                if ($country_result['iso_code']) {
                    $country_code = $country_result['iso_code'];
                }

                $response = $this->getList($frontend_key, $shipping_agent, $delivery_address->address1, $delivery_address->postcode, $country_code, $service_point_id);
                break;

            case 'save_address':
                $cart = Context::getContext()->cart;

                $service_point_address = [
                    'company' => Tools::getValue('company_name'),
                    'address' => Tools::getValue('address'),
                    'address2' => Tools::getValue('service_point_id'),
                    'zip_code' => Tools::getValue('zip_code'),
                    'city' => Tools::getValue('city'),
                    'shipping_agent' => Tools::getValue('shipping_agent'),
                ];

                $sql = new DbQuery();
                $sql
                    ->select('service_point')
                    ->from('shipmondo_selected_service_points')
                    ->where('id_cart = ' . (int) $cart->id);
                $result = Db::getInstance()->getRow($sql);

                if (!$result) {
                    $save_result = Db::getInstance()->insert(
                        'shipmondo_selected_service_points',
                        [
                            'id_cart' => (int) $cart->id,
                            'service_point' => pSQL(Tools::jsonEncode($service_point_address)),
                            'id_carrier' => (int) $cart->id_carrier,
                        ]
                    );
                } else {
                    $save_result = Db::getInstance()->update(
                        'shipmondo_selected_service_points',
                        [
                            'id_cart' => (int) $cart->id,
                            'service_point' => pSQL(Tools::jsonEncode($service_point_address)),
                            'id_carrier' => (int) $cart->id_carrier,
                        ],
                        'id_cart = ' . (int) $cart->id,
                        1
                    );
                }

                if ($save_result && $cart->update()) {
                    $response['status'] = 'success';
                } else {
                    $response['status'] = 'error';
                }
                break;

            case 'get_address':
                $cart = Context::getContext()->cart;
                $shipping_agent = Tools::getValue('shipping_agent');

                $sql = new DbQuery();
                $sql
                    ->select('service_point')
                    ->from('shipmondo_selected_service_points')
                    ->where('id_cart = ' . (int) $cart->id);
                $result = Db::getInstance()->getRow($sql);

                if ($result) {
                    $service_point = Tools::jsonDecode($result['service_point']);

                    if ($shipping_agent === $service_point->shipping_agent) {
                        $response['status'] = 'success';
                        $response['service_point'] = $service_point;
                        break;
                    }
                }

                $response['status'] = 'error';
                break;

            default:
                break;
        }

        echo Tools::jsonEncode($response);
        die;
    }

    private function getList($frontend_key, $shipping_agent, $address, $zip_code, $country = 'DK', $selected_service_point_id = null)
    {
        $method = 'GET';
        $url = 'https://service-points.shipmondo.com/pickup-points.json';
        $data = [
            'frontend_key' => $frontend_key,
            'agent' => $shipping_agent,
            'zipcode' => $zip_code,
            'country' => $country,
            'address' => $address,
        ];

        $response = [];

        if (empty($zip_code) || empty($address) || empty($frontend_key)) {
            return [
                'status' => false,
                'error' => $this->l('Enter zipcode and address to see pickup points'),
            ];
        }

        $service_points = Tools::jsonDecode($this->callShipmondoAPI($method, $url, $data));

        if (empty($service_points)) {
            return [
                'status' => false,
                'error' => $this->l('Please add a valid delivery module key in back office.'),
            ];
        }

        if (!empty($service_points->message)) {
            if ($service_points->message === 'Invalid frontend_key') {
                return [
                    'status' => false,
                    'error' => $this->l('Please add a valid delivery module key in back office.'),
                ];
            } else {
                return [
                    'status' => false,
                    'error' => $service_points->message,
                ];
            }
        }

        $frontend_type = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
        if (!$frontend_type) {
            $frontend_type = 'popup';
        }

        $response['service_points'] = $service_points;
        $response['frontend_type'] = $frontend_type;
        $response['status'] = 'success';
        $response['map'] = $this->module->fetch('module:shipmondo/views/templates/front/map.tpl');

        if (empty($selected_service_point_id)) {
            $selected_service_point_id = 0;
        }

        $this->context->smarty->assign([
            'service_points' => $service_points,
            'selected_service_point_id' => $selected_service_point_id,
            'shipping_agent' => $shipping_agent,
            'shipping_agent_logo' => _MODULE_DIR_ . 'shipmondo/views/img/' . $shipping_agent . '.png',
        ]);
        $response['service_points_html'] = $this->module->fetch('module:shipmondo/views/templates/front/' . Tools::strtolower($frontend_type) . '/service_points.tpl');

        return $response;
    }

    private function callShipmondoAPI($method, $url, $data = false)
    {
        $curl = curl_init();
        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default:
                if ($data) {
                    $url = sprintf('%s?%s', $url, http_build_query($data));
                }
        }

        // Optional Authentication
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, 'username:password');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}
