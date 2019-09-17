<?php

class ShipmondoShippingServicePointsModuleFrontController extends ModuleFrontController {
    public function initContent() {
        $response = [];
        switch (Tools::getValue('method')) {
            case 'get_list':
                $oShoplist_controlr = new PakkelabelsShoplistController();
                $cart = Context::getContext()->cart;

                $sql = new DbQuery();
                $sql
                    ->select('shop_data')
                    ->from(_DB_PREFIX_ . 'pakkelabel_carts')
                    ->where('id_cart = '. (int) $cart->id);
                $result = Db::getInstance()->getRow($sql);

                $service_point_id = null;
                if($result) {
                    $service_point_id = Tools::jsonDecode($result['shop_data'])->address2;
                    $service_point_id = preg_replace('/\D/', '', $service_point_id);
                }

                $shipping_agent = Tools::getValue('shipping_agent');
                $zip_code = Tools::getValue('zip_code');
                $address = Tools::getValue('address');
                $country_code = 'DK';
                $amount_of_points = 10;
                $frontend_key = Configuration::get('PAKKELABELS_SHIPPING_FRONTEND_KEY');

                $delivery_address = new Address($cart->id_address_delivery);

                // Get country iso code by country id
                $sql = new DbQuery();
                $sql
                    ->select('iso_code')
                    ->from(_DB_PREFIX_ . 'country')
                    ->where('id_country = "'. pSQL($delivery_address->id_country) . '"');
                $country_result = Db::getInstance()->getRow($sql);

                if ($country_result['iso_code'])
                    $country_code = $country_result['iso_code'];

                $response = $oShoplist_controlr->getshoplist($zip_code, $shipping_agent, $frontend_key, $address, $country_code, $service_point_id);
                break;

            case 'save_address':
                //gets current cart
                $cart = Context::getContext()->cart;
                $response['cart'] = $cart;

                // Get country id from ISO code
                $service_point_address = [
                    'company' => Tools::getValue('company_name'),
                    'address' => Tools::getValue('address'),
                    'address2' => Tools::getValue('service_point_id'),
                    'postcode' => Tools::getValue('zip_code'),
                    'city' => Tools::getValue('city'),
                ];

                $sql = new DbQuery();
                $sql
                    ->select('id_pkl_cart')
                    ->from(_DB_PREFIX_ . 'pakkelabel_carts')
                    ->where('id_cart = '. (int) $cart->id);
                $result = Db::getInstance()->getRow($sql);

                if (!$result) {
                    $save_result = Db::getInstance()->insert(
                        _DB_PREFIX_ . 'pakkelabel_carts',
                        [
                            'id_cart' => (int) $cart->id,
                            'shop_data' => pSQL(Tools::jsonEncode($service_point_address)),
                            'id_carrier' => (int) $cart->id_carrier
                        ]
                    );
                } else {
                    $save_result = Db::getInstance()->insert(
                        _DB_PREFIX_ . 'pakkelabel_carts',
                        [
                            'id_cart' => (int) $cart->id,
                            'shop_data' => pSQL(Tools::jsonEncode($service_point_address)),
                            'id_carrier' => (int) $cart->id_carrier
                        ],
                        'id_cart = ' . (int) $cart->id,
                        1
                    ); 
                }
                
                if ($save_result && $cart->update())
                    $response['status'] = 'success';
                else
                    $response['status'] = 'error';
                break;
            
            case 'get_address':
                $sql = new DbQuery();
                $sql
                    ->select('shop_data')
                    ->from(_DB_PREFIX_ . 'pakkelabel_carts')
                    ->where('id_cart = '. (int) $cart->id);
                $result = Db::getInstance()->getRow($sql);

                if($result) {
                    $response['status'] = 'success';

                    $json = Tools::jsonDecode($result['shop_data']);
                    
                    // TODO Test if fix is needed now
                    /*
                    $json->company =  $this->fixEncodedChars($json->company); 
                    $json->address = $this->fixEncodedChars($json->address);
                    $json->city = $this->fixEncodedChars($json->city);
                    */

                    $response['service_point'] = $json;
                } else
                    $response['status'] = 'error';
                break;

            default:
                break;
        }

        echo Tools::jsonEncode($response);
        die;
    }   

    private function fixEncodedChars($string) {
        return html_entity_decode(preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $string));
    }
}