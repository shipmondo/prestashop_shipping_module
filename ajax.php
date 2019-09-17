<?php
/**
 *  @author    Shipmondo
 *  @copyright 2019 Shipmondo
 *  @license   All rights reserved
 */

require_once __DIR__ . '../../../config/config.inc.php';
require_once __DIR__ . '../../../init.php';
require_once 'controllers/front/FrontPakkelabelsShopListController.php';

$response = [];
switch (Tools::getValue('method')) {
    case 'getCart':
        $cart = Context::getContext()->cart;
        die(Tools::jsonEncode($cart));

    case 'ajaxGetShopList':
        $oShoplist_controlr = new PakkelabelsShoplistController();
        $cart = Context::getContext()->cart;

        $sql = 'SELECT `shop_data` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        $servicePointId = null;
        if($result) {
            $servicePointId = Tools::jsonDecode(str_rot13($result['shop_data']))->address2;
            $servicePointId = preg_replace('/\D/', '', $servicePointId);
        }

        $sShippinAgent = Tools::getValue('sShippinAgent');
        $iZipcode = Tools::getValue('iZipcode');
        $iAddress = Tools::getValue('iAddress');
        $sCountry = 'DK';
        $iNumber_shops = 10;
        $sFrontend_key = Configuration::get('PAKKELABELS_SHIPPING_FRONTEND_KEY');

        $myAddress = new Address($cart->id_address_delivery);

        // Get country iso code by country id
        $sql = 'SELECT `iso_code` FROM `' . _DB_PREFIX_ . 'country` WHERE '
        . '`id_country` = "' . $myAddress->id_country . '"';
        $country_result = Db::getInstance()->getRow($sql);

        if ($country_result['iso_code']) {
            $sCountry = $country_result['iso_code'];
        }

        $sShopList = $oShoplist_controlr->getshoplist($iZipcode, $sShippinAgent, $sFrontend_key, $iAddress, $sCountry, $servicePointId);

        die(Tools::jsonEncode($sShopList));

    case 'ajaxUpdatePrimaryAddress':
        if ($cart->update()) {
            $response['status'] = 'success';
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

        // Get country id from ISO code
        $pakkelabels = [
            'company' => $sCompanyName,
            'address' => $sAdress,
            'address2' => $sPacketshop_id,
            'postcode' => $iZipcode,
            'city' => $sCity,
        ];

        // Save in db
        $sql = 'SELECT `id_pkl_cart` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = \'' . str_rot13(Tools::jsonEncode($pakkelabels)) . '\', '
            . '`id_carrier` = ' . (int) $cart->id_carrier;
        } else {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = \'' . str_rot13(Tools::jsonEncode($pakkelabels)) . '\', '
            . '`id_carrier` = ' . (int) $cart->id_carrier . ' '
            . 'WHERE `id_cart` = ' . (int) $cart->id;
        }

        $return = Db::getInstance()->execute($sql);
        
        if ($return && $cart->update()) {
            $response['status'] = 'success';
            $response['address_id'] = $cart->id_address_delivery;
        } else {
            $response['status'] = 'error';
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

        $pakkelabels = [
            'company' => $sCompanyName,
            'address' => $sAdress,
            'address2' => $sPacketshop_id,
            'postcode' => $iZipcode,
            'city' => $sCity,
            //'id_country' => $country_result['id_country'],
        ];

        // Save in db
        $sql = 'SELECT `id_pkl_cart` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = \'' . str_rot13(Tools::jsonEncode($pakkelabels)) . '\', '
            . '`id_carrier` = ' . (int) $cart->id_carrier;

            $response['sql'] = $sql;
        } else {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = \'' . str_rot13(Tools::jsonEncode($pakkelabels)) . '\', '
            . '`id_carrier` = ' . (int) $cart->id_carrier . ' '
            . 'WHERE `id_cart` = ' . (int) $cart->id;
        }

        $return = Db::getInstance()->execute($sql);

        if ($cart->update()) {
            $response['status'] = 'success';
            $response['address_id'] = $cart->id_address_delivery;
        } else {
            $response['status'] = 'error';
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
        $updateAddress = (Tools::getValue('updateAddress')) ? Tools::getValue('updateAddress') : false;

        //gets current cart
        $cart = Context::getContext()->cart;

        $pakkelabels = [
            'company' => $sCompanyName,
            'address' => $sAdress,
            'address2' => $sPacketshop_id,
            'postcode' => $iZipcode,
            'city' => $sCity,
            //'id_country' => $country_result['id_country'],
        ];

        // Save in db
        $sql = 'SELECT `id_pkl_cart` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = \'' . str_rot13(Tools::jsonEncode($pakkelabels)) . '\', '
            . '`id_carrier` = ' . (int) $cart->id_carrier;

            $response['sql'] = $sql;
        } else {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'pakkelabel_carts` SET '
            . '`id_cart` = ' . (int) $cart->id . ', '
            . '`shop_data` = \'' . str_rot13(Tools::jsonEncode($pakkelabels)) . '\', '
            . '`id_carrier` = ' . (int) $cart->id_carrier . ' '
            . 'WHERE `id_cart` = ' . (int) $cart->id;
        }

        $return = Db::getInstance()->execute($sql);

        if ($cart->update()) {
            $response['status'] = 'success';
            $response['id_carrier'] = $cart->id_carrier;
        } else {
            $response['status'] = 'error';
        }
        die(Tools::jsonEncode($response));
    case 'getTempCartAddress':
        $cart = Context::getContext()->cart;
        $sql = 'SELECT `shop_data` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
        . '`id_cart` = ' . (int) $cart->id;
        $result = Db::getInstance()->getRow($sql);

        if($result) {
            $response['status'] = 'success';

            $json = Tools::jsonDecode(str_rot13($result['shop_data']));
            $json->company =  fixEncodedChars($json->company);
            $json->address = fixEncodedChars($json->address);
            $json->city = fixEncodedChars($json->city);

            $response['service_point'] = $json;
        } else
            $response['status'] = 'error';

        die(Tools::jsonEncode($response));
    default:
        break;
}

function fixEncodedChars($string) {
    return html_entity_decode(preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $string));
}

exit;
