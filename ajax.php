<?php
/**
* NOTICE OF LICENSE
*
*  @author    Pakkelabels
*  @copyright 2017 Pakkelabel
*  @license   All rights reserved
*/

require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');
require_once('controllers/pakkelabels_shoplist_controller.php');
$response=array();
switch (Tools::getValue('method')) {
    case 'getCart':
        $cart = Context::getContext()->cart;
        die(Tools::jsonEncode($cart));

    case 'ajaxGetShopList':
        $oShoplist_controller = new Pakkelabels_Shoplist_Controller();
        $cart = Context::getContext()->cart;

        $sShippinAgent = Tools::getValue('sShippinAgent');
        $iZipcode = Tools::getValue('iZipcode');
        $sCountry = 'DK';
        $iNumber_shops = 10;
        $sFrontend_key = Configuration::get('PAKKELABELS_SHIPPING_FRONTEND_KEY');
        
        $myAddress = new Address($cart->id_address_delivery);
        
        // Get country iso code by country id
        $sql = 'SELECT `iso_code` FROM `' . _DB_PREFIX_ . 'country` WHERE '
        . '`id_country` = "'.$myAddress->id_country.'"';
        $country_result = Db::getInstance()->getRow($sql);

        if ($country_result['iso_code']) {
            $sCountry = $country_result['iso_code'];
        }

        $sShopList = $oShoplist_controller->get_shop_list_callback($iZipcode, $sShippinAgent, $iNumber_shops, $sFrontend_key, $sCountry);
        die(Tools::jsonEncode($sShopList));

    case 'ajaxUpdatePrimaryAddress':
        if ($cart->update()) {
            $response['status'] = "success";
            $response['zippi'] = $iZipcode;
        } else {
            $response['status'] = 'error';
        }

        die(Tools::jsonEncode($response));

    case 'ajaxTempCartAddress':
        $sCompanyName = Tools::getValue('sCompany_name');
        $sPacketshop_id = Tools::getValue('sPacketshop_id');
        $sAdress = Tools::getValue('sAdress');
        $sCity = Tools::getValue('sCity');
        $iZipcode = Tools::getValue('iZipcode');

        //gets current cart
        $cart = Context::getContext()->cart;
        $response['cart'] = $cart;
        
        // Get country id from ISO code // Default DKK
        /* $sql = 'SELECT `id_country` FROM `' . _DB_PREFIX_ . 'country` WHERE '
		. '`iso_code` = "DK"';
		$country_result = Db::getInstance()->getRow($sql); */

        $pakkelabels = array(
            'company' => $sCompanyName,
            'address' => $sAdress,
            'address2' => $sPacketshop_id,
            'postcode' => $iZipcode,
            'city' => $sCity,
        );
        // Save in db
        $sql = 'SELECT `id_pkl_cart` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = "' . base64_encode(Tools::jsonEncode($pakkelabels)) . '", '
            . '`id_carrier` = ' . (int) $cart->id_carrier;

            $response['sql'] = $sql;
        } else {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = "' . base64_encode(Tools::jsonEncode($pakkelabels)) . '", '
            . '`id_carrier` = ' . (int) $cart->id_carrier . ' '
            . 'WHERE `id_cart` = ' . (int) $cart->id;
        }

        $return = Db::getInstance()->execute($sql);

        if ($cart->update()) {
            $response['status'] = "success";
            $response['address_id'] = $cart->id_address_delivery;
        } else {
            $response['status'] = $iZipcode;
        }

        die(Tools::jsonEncode($response));

    case 'ajaxTempCartAddressOPC':
        $sCompanyName = Tools::getValue('sCompany_name');
        $sPacketshop_id = Tools::getValue('sPacketshop_id');
        $sAdress = Tools::getValue('sAdress');
        $sCity = Tools::getValue('sCity');
        $iZipcode = Tools::getValue('iZipcode');

        //Load current cart
        $cart = Context::getContext()->cart;

        $pakkelabels = array(
            'company' => $sCompanyName,
            'address' => $sAdress,
            'address2' => $sPacketshop_id,
            'postcode' => $iZipcode,
            'city' => $sCity,
            //'id_country' => $country_result['id_country'],
        );

        // Save in db
        $sql = 'SELECT `id_pkl_cart` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = "' . base64_encode(Tools::jsonEncode($pakkelabels)) . '", '
            . '`id_carrier` = ' . (int) $cart->id_carrier;

            $response['sql'] = $sql;
        } else {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = "' . base64_encode(Tools::jsonEncode($pakkelabels)) . '", '
            . '`id_carrier` = ' . (int) $cart->id_carrier . ' '
            . 'WHERE `id_cart` = ' . (int) $cart->id;
        }

        $return = Db::getInstance()->execute($sql);

        if ($cart->update()) {
            $response['status'] = "success";
            $response['address_id'] = $cart->id_address_delivery;
        } else {
            $response['status'] = $iZipcode;
        }
        die(Tools::jsonEncode($response));

    case 'ajaxTempCartAddressOPCGuest':
        $sFirstname = Tools::getValue('sFirstname');
        $sLastname = Tools::getValue('sLastname');
        $sCompanyName = Tools::getValue('sCompany_name');
        $sPacketshop_id = Tools::getValue('sPacketshop_id');
        $sAdress = Tools::getValue('sAdress');
        $sCity = Tools::getValue('sCity');
        $iZipcode = Tools::getValue('iZipcode');
        $updateAddress = ( Tools::getValue('updateAddress') ) ? Tools::getValue('updateAddress') : false ;

        //gets current cart
        $cart = Context::getContext()->cart;

        $pakkelabels = array(
            'company' => $sCompanyName,
            'address' => $sAdress,
            'address2' => $sPacketshop_id,
            'postcode' => $iZipcode,
            'city' => $sCity,
            //'id_country' => $country_result['id_country'],
        );

        // Save in db
        $sql = 'SELECT `id_pkl_cart` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = "' . base64_encode(Tools::jsonEncode($pakkelabels)) . '", '
            . '`id_carrier` = ' . (int) $cart->id_carrier;

            $response['sql'] = $sql;
        } else {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = "' . base64_encode(Tools::jsonEncode($pakkelabels)) . '", '
            . '`id_carrier` = ' . (int) $cart->id_carrier . ' '
            . 'WHERE `id_cart` = ' . (int) $cart->id;
        }

        $return = Db::getInstance()->execute($sql);

        if ($cart->update()) {
            $response['status'] = "success";
            $response['id_carrier'] = $cart->id_carrier;
        } else {
            $response['status'] = "error";
        }
        die(Tools::jsonEncode($response));
    default:
        break;
}
exit;
