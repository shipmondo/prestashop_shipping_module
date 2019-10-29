<?php
/**
 *  @author    Shipmondo
 *  @copyright 2019 Shipmondo
 *  @license   All rights reserved
 */

class ShipmondoExtentionsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $response = [];
        switch (Tools::getValue('method')) {
            case 'get_address':
                $address_id = Tools::getValue('address_id');
                $response['address'] = new Address($address_id);
                $response['status'] = $response['address']->id == $address_id ? 'success' : 'error';
                break;

            default:
                break;
        }

        echo Tools::jsonEncode($response);
        die;
    }
}
