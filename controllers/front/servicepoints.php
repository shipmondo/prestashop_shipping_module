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
                    $service_point_id = Tools::jsonDecode($result['service_point'])->id;
                }

                $carrier_code = Tools::getValue('carrier_code');
                $zip_code = Tools::getValue('zip_code');
                $address = Tools::getValue('address');
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

                $response = $this->getList($frontend_key, $carrier_code, $delivery_address->address1, $delivery_address->postcode, $country_code, $service_point_id);
                break;

            case 'save_address':
                $cart = Context::getContext()->cart;

                $carrier_code = Tools::getValue('carrier_code');
                $service_point_id = Tools::getValue('service_point_id');

                $service_point_address = [
                    'id'            => $service_point_id,
                    'company_name'  => Tools::getValue('company_name'),
                    'address'       => Tools::getValue('address'),
                    'address2'      => "ID: {$carrier_code}-{$service_point_id}",
                    'zip_code'      => Tools::getValue('zip_code'),
                    'city'          => Tools::getValue('city'),
                    'carrier_code'  => $carrier_code,
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
                $carrier_code = Tools::getValue('carrier_code');

                $sql = new DbQuery();
                $sql
                    ->select('service_point')
                    ->from('shipmondo_selected_service_points')
                    ->where('id_cart = ' . (int) $cart->id);
                $result = Db::getInstance()->getRow($sql);

                if ($result) {
                    $service_point = Tools::jsonDecode($result['service_point']);

                    if ($carrier_code === $service_point->carrier_code) {
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

    private function getList($frontend_key, $carrier_code, $address, $zip_code, $country = 'DK', $selected_service_point_id = null)
    {
        $method = 'GET';
        $url = 'https://service-points.shipmondo.com/pickup-points.json';
        $data = [
            'frontend_key'          => $frontend_key,
            'request_url'           => _PS_BASE_URL_,
            'module_version'        => Module::getInstanceByName('shipmondo')->version, //TODO is there a easier way of obtaining module version?
            'shipping_module_type'  => 'prestashop',
            
            'carrier_code'          => $carrier_code,
            'zipcode'               => $zip_code,
            'country'               => $country,
            'address'               => $address,
        ];

        $response = [];

        if (empty($zip_code) || empty($address) || empty($frontend_key)) {
            return [
                'status' => "error",
                'error' => $this->l('Enter zipcode and address to see pickup points'),
            ];
        }

        $service_points = Tools::jsonDecode($this->callShipmondoAPI($method, $url, $data));

        if (empty($service_points)) {
            return [
                'status' => "error",
                'error' => $this->l('No pickup points found. Please confirm address.'),
            ];
        }

        if (!empty($service_points->message)) {
            if ($service_points->message === 'Invalid frontend_key') {
                return [
                    'status' => "error",
                    'error' => $this->l('Please add a valid delivery module key in back office.'),
                ];
            } else {
                return [
                    'status' => "error",
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

        if (empty($selected_service_point_id)) {
            $selected_service_point_id = 0;
        }

        
        $count = count($service_points);
        $this->context->smarty->assign([
            'service_points' => $service_points,
            'selected_service_point_id' => $selected_service_point_id,
            'carrier_code' => $carrier_code,
            'carrier_logo' => _MODULE_DIR_ . 'shipmondo/views/img/' . $carrier_code . '.png',
            'service_points_json' => htmlentities(Tools::jsonEncode($service_points), ENT_QUOTES, 'UTF-8'),
            'service_points_count' => sprintf($this->_n($this->l('%s pickup point found'), $this->l('%s pickup points found'), $count), $count)
        ]);
        $response['service_points_html'] = $this->module->fetch('module:shipmondo/views/templates/front/' . Tools::strtolower($frontend_type) . '/content.tpl');

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

    // In place of WordPress function _n https://developer.wordpress.org/reference/functions/_n/
    private function _n($single, $plural, $amount)
    {
        if ($amount == 1) {
            return $single;
        }
        return $plural;
    }
}
