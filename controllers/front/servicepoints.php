<?php
/**
 *  @author    Shipmondo
 *  @copyright 2023 Shipmondo
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
                    $service_point_id = json_decode($result['service_point'])->id;
                }

                $carrier_code       = Tools::getValue('carrier_code');
                $last_carrier_code  = Tools::getValue('last_carrier_code');
                $last_address       = (object) Tools::getValue('last_address');
                $frontend_key       = Configuration::get('SHIPMONDO_FRONTEND_KEY');

                $delivery_address = new Address($cart->id_address_delivery);

                // Check if reload of service point is needed
                $address_changed = $this->hasAddressChanged($last_address, $delivery_address);
                if (!$address_changed && $carrier_code == $last_carrier_code) {
                    $response['address_changed'] = false;
                    $response['status'] = 'success';
                    break;
                }

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
                $response['new_address'] = [
                    'id_country' => $delivery_address->id_country,
                    'address1' => $delivery_address->address1,
                    'postcode' => $delivery_address->postcode,
                ];
                $response['address_changed'] = true;

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
                            'service_point' => pSQL(json_encode($service_point_address)),
                            'id_carrier' => (int) $cart->id_carrier,
                        ]
                    );
                } else {
                    $save_result = Db::getInstance()->update(
                        'shipmondo_selected_service_points',
                        [
                            'id_cart' => (int) $cart->id,
                            'service_point' => pSQL(json_encode($service_point_address)),
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
                    $service_point = json_decode($result['service_point']);

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

        echo json_encode($response);
        exit;
    }

    private function getList($frontend_key, $carrier_code, $address, $zip_code, $country = 'DK', $selected_service_point_id = null)
    {
        $module = Module::getInstanceByName('shipmondo');

        $method = 'GET';
        $url = 'https://service-points.shipmondo.com/pickup-points.json';
        $data = [
            'frontend_key'          => $frontend_key,
            'request_url'           => _PS_BASE_URL_,
            'request_version'       => _PS_VERSION_,
            'module_version'        => $module->version,
            'shipping_module_type'  => 'prestashop',
            'carrier_code'          => $carrier_code,
            'zipcode'               => $zip_code,
            'country'               => $country,
            'address'               => $address,
        ];

        $response = [];

        if (empty($zip_code) || empty($address) || empty($frontend_key)) {
            return [
                'status' => 'error',
                'error' => $module->l('Enter zipcode and address to see pickup points', 'servicepoints'),
            ];
        }

        $service_points = json_decode($this->callShipmondoAPI($method, $url, $data));

        if (empty($service_points)) {
            return [
                'status' => 'error',
                'error' => $module->l('No pickup points found. Please confirm address.', 'servicepoints'),
            ];
        }

        if (!empty($service_points->message)) {
            if ($service_points->message === 'Invalid frontend_key') {
                return [
                    'status' => 'error',
                    'error' => $module->l('Please add a valid delivery module key in back office.', 'servicepoints'),
                ];
            } else {
                return [
                    'status' => 'error',
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

        $single = $module->l('%s pickup point found', 'servicepoints');
        $plural = $module->l('%s pickup points found', 'servicepoints');
        $count = count($service_points);
        $count_text = $this->amount($single, $plural, $count);

        $this->context->smarty->assign([
            'service_points' => $service_points,
            'selected_service_point_id' => $selected_service_point_id,
            'carrier_code' => $carrier_code,
            'carrier_logo' => _MODULE_DIR_ . 'shipmondo/views/img/' . $carrier_code . '.png',
            'service_points_json' => json_encode($service_points),
            'service_points_count' => sprintf($count_text, $count),
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

    private function amount($single, $plural, $amount)
    {
        if ($amount == 1) {
            return $single;
        }
        return $plural;
    }

    private function hasAddressChanged($old_address, $new_address)
    {
        return !empty($old_address)
            || $old_address->id_country != $new_address->id_country
            || $old_address->postcode != $new_address->postcode
            || $old_address->address1 != $new_address->address1;
    }
}
