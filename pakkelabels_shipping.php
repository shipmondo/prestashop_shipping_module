<?php
/**
*  @author    Pakkelabels
*  @copyright 2017 Pakkelabel
*  @license   All rights reserved
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class pakkelabels_shipping extends CarrierModule
{
    const PREFIX = 'pakkelabels_shipping_';
    protected $_hooks = array(
        'actionCarrierUpdate',
        'newOrder',
        'header',
        'footer',
    );
    protected $_carriers = array(
        'pakkelabels_GLS' => 'GLS PakkeShop',
        'pakkelabels_GLS_private' => 'GLS - Omdeling til privat',
        'pakkelabels_GLS_business' => 'GLS - Omdeling til erhverv',
        'pakkelabels_PostNord' => 'PostNord Valgfrit udleveringssted',
        'pakkelabels_PostNord_business' => 'PostNord - Omdeling til erhverv',
        'pakkelabels_PostNord_private' => 'PostNord - Omdeling til privat',
        'pakkelabels_DAO' => 'DAO Pakkeshop',
        'pakkelabels_dao_direct' => 'DAO Direkte',
        'pakkelabels_bring' => 'Bring - Valgfrit udleveringssted',
        'pakkelabels_bring_private' => 'Bring - Aftenlevering til privat',
        'pakkelabels_bring_business' => 'Bring - Omdeling til erhverv',
    );

    public function __construct()
    {
        $this->name = 'pakkelabels_shipping';
        $this->tab = 'shipping_logistics';
        $this->version = '1.2.4';
        $this->v16 = _PS_VERSION_ >= "1.6.0.0";
        $this->v17 = _PS_VERSION_ >= "1.7.0.0";
        $this->author = 'Pakkelabels.dk';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Pakkelabels.dk Shipping');
        $this->description = $this->l('GLS, PostNord, DAO and Bring Shipping for PrestaShop');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        if ($this->v17) {
            $this->registerHook('displayHeader');
        }
        $this->registerHook('newOrder');
    }

    public function hookNewOrder($params)
    {
        $carrier_id = $params['order']->id_carrier;
        
        $order = new Order((int) ($params['order']->id));
        
        /* $pakkelabel_info = $context->cookie->pakkelabels; */
        $iPakkelabels_ID_GLS = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_GLS'));
        $iPakkelabels_ID_DAO = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_DAO'));
        $iPakkelabels_ID_PostNord = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_POSTNORD'));
        $iPakkelabels_ID_Bring = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_BRING'));
        
        $add_address = false;
        if ($iPakkelabels_ID_GLS->id == $carrier_id) {
            // Add address
            $alias = 'GLS';
            $add_address = true;
        }
        if ($iPakkelabels_ID_DAO->id == $carrier_id) {
            // Add address
            $alias = 'DAO';
            $add_address = true;
        }
        if ($iPakkelabels_ID_PostNord->id == $carrier_id) {
            // Add address
            $alias = 'PDK';
            $add_address = true;
        }
        if ($iPakkelabels_ID_Bring->id == $carrier_id) {
            // Add address
            $alias = 'Bring';
            $add_address = true;
        }
        if ($add_address) {
            //$p_data = json_decode($pakkelabel_info); // Deprecated
            /* Find shop information in db */
            if (isset($params['cart'])) {
                $sql = 'SELECT `shop_data` FROM `' . _DB_PREFIX_ . 'pakkelabel_carts` WHERE '
                . '`id_cart` = ' . (int) $params['cart']->id;
                $result = Db::getInstance()->getRow($sql);

                if ($result['shop_data']) {
                    $p_data = Tools::jsonDecode(base64_decode($result['shop_data']));

                    $id_address_max = (int) Db::getInstance()->getValue('SELECT MAX(`id_address`) FROM 
                    `' . _DB_PREFIX_ . 'address`');
                    $new_id = $id_address_max + 1;

                    $myAddress = new Address($order->id_address_delivery);
                    $old_invoice_id = $order->id_address_invoice;
                    $old_address_id = $order->id_address_delivery;
                    $newAddress = clone $myAddress;

                    $newAddress->id = $new_id;
                    $newAddress->company = $p_data->company;
                    $newAddress->address1 = $p_data->address;

                    $newAddress->address2 = $p_data->address2;
                    $newAddress->postcode = $p_data->postcode;
                    $newAddress->city = $p_data->city;
                    //$newAddress->id_country = $p_data->id_country;
                    $newAddress->alias = $alias.': '.trim($p_data->address2);
                    $newAddress->deleted = true; // Make sure user cannot select address for a later order.
                    $newAddress->active = true;

                    $order->id_address_delivery = $new_id;
                    // Update and/or add
                    $newAddress->add();
                    $newAddress->update();
                    $order->save();

                    // Update cart
                    $cart = Context::getContext()->cart;
                    $cart->updateAddressId($old_address_id, $new_id);
                    $cart->id_address_invoice = $old_invoice_id;
                    $cart->id_address_delivery = $new_id;
                    $cart->update();

                    $params['cart']->id_address_delivery = $new_id;
                    $params['cart']->id_address_invoice = $old_invoice_id;
                    $params['cart']->update();

                    $params['order']->id_address_delivery = $new_id;
                    $params['order']->id_address_invoice = $old_invoice_id;
                    $params['order']->update();
                }
            }
        }
    }

    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            $sPakkelabels_Frontend_key = (string)(Tools::getValue('PAKKELABELS_SHIPPING_FRONTEND_KEY'));
            $sPakkelabels_Google_api_key = (string)(Tools::getValue('PAKKELABELS_GOOGLE_API_KEY'));
            $iPakkelabels_ID_GLS = (string)(Tools::getValue('PAKKELABELS_SHIPPING_ID_GLS'));
            $iPakkelabels_ID_DAO = (string)(Tools::getValue('PAKKELABELS_SHIPPING_ID_DAO'));
            $iPakkelabels_ID_PostNord = (string)(Tools::getValue('PAKKELABELS_SHIPPING_ID_POSTNORD'));
            $iPakkelabels_ID_Bring = (string)(Tools::getValue('PAKKELABELS_SHIPPING_ID_BRING'));
            $pakkelabels_option = (string)(Tools::getValue('PAKKELABELS_FRONT_OPTION'));

            $sError_output = $this->l('The Pakkelabels.dk shipping module, requires 
            all the settings below to be entered correctly and saved
            before the module will operate correctly.') . '</br>';
            $sError_output .= $this->l('Invalid Configuration value(s), please insert the following:');
            $aError = array();
            $bError = false;
            Configuration::updateValue('PAKKELABELS_FRONT_OPTION', $pakkelabels_option);
            if (empty($sPakkelabels_Frontend_key) || !Validate::isGenericName($sPakkelabels_Frontend_key)) {
                $aError[]  = '<a target="_blank" href="https://app.pakkelabels.dk/main/app/#/setting/api">'
                . $this->l('Frontend Key') . '</a>';
                Configuration::updateValue('PAKKELABELS_SHIPPING_FRONTEND_KEY', "");
                $bError = true;
            } else {
                Configuration::updateValue('PAKKELABELS_SHIPPING_FRONTEND_KEY', $sPakkelabels_Frontend_key);
            }

            if (empty($sPakkelabels_Google_api_key) || !Validate::isGenericName($sPakkelabels_Google_api_key)) {
                $aError[] = '<a target="_blank" 
                href="https://developers.google.com/maps/documentation/javascript/get-api-key">'
                . $this->l('Google Map API Key') . '</a>';
                Configuration::updateValue('PAKKELABELS_GOOGLE_API_KEY', "");
                $bError = true;
            } else {
                Configuration::updateValue('PAKKELABELS_GOOGLE_API_KEY', $sPakkelabels_Google_api_key);
            }

            if (empty($iPakkelabels_ID_GLS) || !Validate::isGenericName($iPakkelabels_ID_GLS)) {
                $aError[] = $this->l('GLS Carrier ID');
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_GLS', "");
                $bError = true;
            } else {
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_GLS', $iPakkelabels_ID_GLS);
            }

            if (empty($iPakkelabels_ID_DAO) || !Validate::isGenericName($iPakkelabels_ID_DAO)) {
                $aError[] = $this->l('DAO Carrier ID');
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_DAO', "");
                $bError = true;
            } else {
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_DAO', $iPakkelabels_ID_DAO);
            }

            if (empty($iPakkelabels_ID_PostNord) || !Validate::isGenericName($iPakkelabels_ID_PostNord)) {
                $aError[] = $this->l('PostNord Carrier ID');
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_POSTNORD', "");
                $bError = true;
            } else {
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_POSTNORD', $iPakkelabels_ID_PostNord);
            }
            
            if (empty($iPakkelabels_ID_Bring) || !Validate::isGenericName($iPakkelabels_ID_Bring)) {
                $aError[] = $this->l('Bring Carrier ID');
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_BRING', "");
                $bError = true;
            } else {
                Configuration::updateValue('PAKKELABELS_SHIPPING_ID_BRING', $iPakkelabels_ID_Bring);
            }

            if ($bError == true) {
                foreach ($aError as $key) {
                    $sError_output .= '<li class="test">' . $key . '</li>';
                }
                $output .= $this->displayError($sError_output);
            }
        }
        return $output . $this->displayForm();
    }


    public function displayForm()
    {
        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $all_carriers = Carrier::getCarriers($default_lang, false, false, false, null, ALL_CARRIERS);
        $carriers = array();

        foreach ($all_carriers as $carrier) {
            $carriers[] = array(
                'id_option' => $carrier['id_reference'],
                'name' => $carrier['name'],
            );
        }
        $fields_form=array();
          if (!$this->v17) {
              $type='free';
              $desc='desc';
          }
          else{
               $type='html';
              $desc='html_content';
          }
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Pakkelabels.dk Settings'),
            ),
            'input' => array(
                array(
                    'name' => 'PAKKELABELS_SHIPPING_DESC',
                    'type' => $type,
                    $desc => $this->l('Follow the setup guide for Prestashop'). ':
                    <a href="https://www.pakkelabels.dk/integration/prestashop-fragtmodul/" target="_blank">
                    https://www.pakkelabels.dk/integration/prestashop-fragtmodul/</a>',
                ), 
                array(
                    'name' => 'PAKKELABELS_SHIPPING_DESC_BEES',
                    'type' => $type,
                    $desc => $this->l('Follow the setup guide for thirty bees'). ':
                    <a href="https://www.pakkelabels.dk/integration/thirty-bees-fragtmodul/" target="_blank">
                    https://www.pakkelabels.dk/integration/thirty-bees-fragtmodul/</a>',
                ),
                array(
                    'name' => 'PAKKELABELS_SHIPPING_FRONTEND_KEY',
                    'type' => 'text',
                    'label' => $this->l('Frontend Key'),
                    'desc' => $this->l('Insert Frontend Key here - Get the key from') . ': 
                    <a target="_blank" href="https://app.pakkelabels.dk/main/app/#/setting/api">
                    Pakkelabels.dk</a>',
                    'required' => true,
                ),
                array(
                    'name' => 'PAKKELABELS_GOOGLE_API_KEY',
                    'type' => 'text',
                    'label' => $this->l('Google API Map Key'),
                    'desc' => $this->l('Insert Google API Map Key - Get the key from'). ': 
                    <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">
                    Google</a>' ,
                    'required' => true,
                ),
                array(
                    'name' => 'PAKKELABELS_SHIPPING_ID_GLS',
                    'type' => 'select',
                    'options' => array(
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                    'label' => $this->l('GLS Packetshop'),
                    'required' => true,
                ),
                array(
                    'name' => 'PAKKELABELS_SHIPPING_ID_POSTNORD',
                    'type' => 'select',
                    'options' => array(
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                    'label' => $this->l('PostNord choosen pickuppoint'),
                    'required' => true,
                ),
                array(
                    'name' => 'PAKKELABELS_SHIPPING_ID_DAO',
                    'type' => 'select',
                    'options' => array(
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                    'label' => $this->l('DAO Packetshop'),
                    'required' => true,
                ),
                array(
                    'name' => 'PAKKELABELS_SHIPPING_ID_BRING',
                    'type' => 'select',
                    'options' => array(
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                    'label' => $this->l('Bring choosen pickuppoint'),
                    'required' => true,
                ),
                array(
                    'name' => 'PAKKELABELS_FRONT_OPTION',
                    'type' => 'radio',
                    'values'    => array(
                        array(
                          'id'    => 'active_on',
                          'value' => 'Popup',
                          'label' => $this->l('Popup')
                        ),
                        array(
                          'id'    => 'active_off',
                          'value' => 'dropdown',
                          'label' => $this->l('Dropdown')
                        )
                    ),
                    'label' => $this->l('Select display on checkout'),
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );


        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        if (!$this->v17) {
            $helper->fields_value['PAKKELABELS_SHIPPING_DESC'] = Configuration::get('PAKKELABELS_SHIPPING_DESC');
            $helper->fields_value['PAKKELABELS_SHIPPING_DESC_BEES'] = Configuration::get('PAKKELABELS_SHIPPING_DESC_BEES');
        }
        $helper->fields_value['PAKKELABELS_SHIPPING_FRONTEND_KEY'] = Configuration::get('PAKKELABELS_SHIPPING_FRONTEND_KEY');
        $helper->fields_value['PAKKELABELS_GOOGLE_API_KEY'] = Configuration::get('PAKKELABELS_GOOGLE_API_KEY');
        $helper->fields_value['PAKKELABELS_SHIPPING_ID_GLS'] = Configuration::get('PAKKELABELS_SHIPPING_ID_GLS');
        $helper->fields_value['PAKKELABELS_SHIPPING_ID_POSTNORD'] = Configuration::get('PAKKELABELS_SHIPPING_ID_POSTNORD');
        $helper->fields_value['PAKKELABELS_SHIPPING_ID_DAO'] = Configuration::get('PAKKELABELS_SHIPPING_ID_DAO');
        $helper->fields_value['PAKKELABELS_SHIPPING_ID_BRING'] = Configuration::get('PAKKELABELS_SHIPPING_ID_BRING');
        $helper->fields_value['PAKKELABELS_FRONT_OPTION'] = (Configuration::get('PAKKELABELS_FRONT_OPTION')!='')? Configuration::get('PAKKELABELS_FRONT_OPTION'):'Popup';

        return $helper->generateForm($fields_form);
    }

    protected function createCarriers()
    {
        foreach ($this->_carriers as $key => $value) {
            //Create new carrier
            $carrier = new Carrier();
            $carrier->name = $value;
            $carrier->active = false;
            $carrier->deleted = 0;
            $carrier->shipping_handling = false;
            $carrier->range_behavior = 0;
            $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = $this->l('2-4 days');
            $carrier->shipping_external = true;
            $carrier->is_module = true;
            $carrier->external_module_name = $this->name;
            $carrier->need_range = true;
            if ($carrier->add()) {
                $groups = Group::getGroups(true);
                foreach ($groups as $group) {
                    //check if values exist before insert
                    $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'carrier_group WHERE
                    id_carrier = '.(int) $carrier->id.' AND id_group = '.(int) $group['id_group'];
                    $group_exist = Db::getInstance()->getValue($sql, false);
                    if (!$group_exist) {
                        Db::getInstance()->insert('carrier_group', array(
                            'id_carrier' => (int) $carrier->id,
                            'id_group' => (int) $group['id_group']
                        ), false, false);
                    }
                }
                $rangePrice = new RangePrice();
                $rangePrice->id_carrier = $carrier->id;
                $rangePrice->delimiter1 = '0';
                $rangePrice->delimiter2 = '1000000';
                $rangePrice->add();
                $rangeWeight = new RangeWeight();
                $rangeWeight->id_carrier = $carrier->id;
                $rangeWeight->delimiter1 = '0';
                $rangeWeight->delimiter2 = '1000000';
                $rangeWeight->add();
                $zones = Zone::getZones(true);
                foreach ($zones as $z) {
                    //check if values exist before insert
                    $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'delivery WHERE
                    id_carrier = '.(int) $carrier->id.' AND id_zone = '.(int) $z['id_zone'];
                    $price_range_exist = Db::getInstance()->getValue($sql, false);

                    if (! $price_range_exist) {
                        Db::getInstance()->insert('carrier_zone', array('id_carrier' => (int) $carrier->id, 'id_zone' => (int) $z['id_zone']), false, false);
                        Db::getInstance()->insert('delivery', array('id_carrier' => $carrier->id, 'id_range_price' => (int) $rangePrice->id, 'id_range_weight' => null, 'id_zone' => (int) $z['id_zone'], 'price' => '0'), true, false);

                        if ($this->v17) {
                            Db::getInstance()->insert('delivery', array('id_carrier' => $carrier->id, 'id_range_price' => null, 'id_range_weight' => (int) $rangeWeight->id, 'id_zone' => (int) $z['id_zone'], 'price' => '0'), true, false);
                        } else {
                            Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_ . 'delivery', array('id_carrier' => $carrier->id, 'id_range_price' => null, 'id_range_weight' => (int) $rangeWeight->id, 'id_zone' => (int) $z['id_zone'], 'price' => '0'), 'INSERT');
                        }
                    }
                }

                copy(dirname(__FILE__) . '/views/img/' . $key . '.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg'); //assign carrier logo

                Configuration::updateValue(self::PREFIX . $key, $carrier->id);
                Configuration::updateValue(self::PREFIX . $key . '_reference', $carrier->id);
                
                if ($key == 'pakkelabels_GLS') {
                    Configuration::updateValue('PAKKELABELS_SHIPPING_ID_GLS', $carrier->id);
                } elseif ($key == 'pakkelabels_PostNord') {
                    Configuration::updateValue('PAKKELABELS_SHIPPING_ID_POSTNORD', $carrier->id);
                } elseif ($key == 'pakkelabels_DAO') {
                    Configuration::updateValue('PAKKELABELS_SHIPPING_ID_DAO', $carrier->id);
                } elseif ($key == 'pakkelabels_bring') {
                    Configuration::updateValue('PAKKELABELS_SHIPPING_ID_BRING', $carrier->id);
                }
            }
        }
        return true;
    }

    protected function createDatabaseTables()
    {
        $sql_carts = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pakkelabel_carts` ('
            . '`id_pkl_cart` int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . '`id_cart` int(10), '
            . '`shop_data` text, '
            . '`id_carrier` int(10) '
        . ')';
        $db_instance = DB::getInstance();
        return $db_instance->Execute($sql_carts);
    }
    
    protected function deleteDatabaseTables()
    {
        $sql_carts  = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pakkelabel_carts`';
        $db_instance = DB::getInstance();
        return $db_instance->Execute($sql_carts);
    }

    protected function deleteCarriers()
    {
        foreach ($this->_carriers as $key => $value) {
            $tmp_carrier_id = Configuration::get(self::PREFIX . $key);
            $value = 0;
            $carrier = new Carrier($tmp_carrier_id);
            $carrier->delete();
        }
        return true;
    }
    protected function deleteSettings()
    {
        Configuration::deleteByName('PAKKELABELS_SHIPPING_ID_GLS');
        Configuration::deleteByName('PAKKELABELS_SHIPPING_ID_POSTNORD');
        Configuration::deleteByName('PAKKELABELS_SHIPPING_ID_DAO');
        Configuration::deleteByName('PAKKELABELS_SHIPPING_ID_BRING');
        Configuration::deleteByName('PAKKELABELS_FRONT_OPTION');
        return true;
    }
    public function uninstall()
    {
        if (parent::uninstall()) {
            if ($this->v17) {
                $this->_hooks[]='displayHeader';
            }
            foreach ($this->_hooks as $hook) {
                if (!$this->unregisterHook($hook)) {
                    return false;
                }
            }
            
            if (!$this->deleteCarriers()) {
                return false;
            }
            
            if (!$this->deleteSettings()) {
                return false;
            }

            if (!$this->deleteDatabaseTables()) {
                return false;
            }
            return true;
        }
        return false;
    }
    public function install()
    {
        if (parent::install()) {
            if ($this->v17) {
                $this->_hooks[]='displayHeader';
            }
            foreach ($this->_hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }
            if (!$this->createCarriers()) { //function for creating new currier
                return false;
            }

            if (!$this->createDatabaseTables()) {
                return false;
            }
            return true;
        }
        return false;
    }
    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $shipping_cost;
    }
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }
    public function hookActionCarrierUpdate($params)
    {
        if ($params['carrier']->id_reference == Configuration::get(self::PREFIX . 'swipbox_reference')) {
            Configuration::updateValue(self::PREFIX . 'swipbox', $params['carrier']->id);
        }
    }
    public function hookDisplayHeader($params)
    {
        // Get shipping method id from id reference
        $gls = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_GLS'));
        $dao = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_DAO'));
        $pdk = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_POSTNORD'));
        $bring = Carrier::getCarrierByReference(Configuration::get('PAKKELABELS_SHIPPING_ID_BRING'));
    
        $page = $this->context->controller->php_self;
        
        if (!$page) {
            $page = $this->context->controller->page_name;
        }

        if (($page == 'order-opc' && !$this->v17) || $page == 'order' || $page == 'module-supercheckout-supercheckout') {
            Media::addJsDef(array(
                'sPage' => $page,
                'sPakkelabels_find_shop_btn_text' => $this->l('Find nearest pickup point'),
                'sSelected_shop_header' => $this->l('Please enter a zipcode to select a pickup point'),
                'sPakkelabels_zipcode_field' => $this->l('Zipcode'),
                'sPakkelabel_modal_header_h4' => $this->l('Choose pickup point'),
                'sPakkelabel_open_map' => $this->l('Show Map'),
                'sPakkelabel_hide_map' => $this->l('Hide Map'),
                'sChoose_stop_btn' => $this->l('Choose'),
                'iPakkelabels_ID_GLS' => $gls->id,
                'iPakkelabels_ID_DAO' => $dao->id,
                'iPakkelabels_ID_POSTNORD' => $pdk->id,
                'iPakkelabels_ID_BRING' => $bring->id,
                'iPakkelabels_ID_WINDOW' => Configuration::get('PAKKELABELS_FRONT_OPTION'),
                'selected_shop_header' => $this->l('Currently choosen pickup point:'),
                'error_message_zipcode' => $this->l('The zipcode must be 4 numbers long, and numeric - please try again'),
                'error_no_cords_found' => $this->l('* Couldnt mark this pickup point on the map'),
                'dataRoot' => Tools::getProtocol(Tools::usingSecureMode()) . $_SERVER['HTTP_HOST'] . $this->getPathUri(),
                'error_no_shop_selected' => $this->l('You must choose a pickup point before, you can proceed in with the checkout with this shipping method'),
                'error_login_before' => $this->l('Before proceeding with the checkout, you must either login, or use the instant guest checkout - above'),
            ));
            if (_PS_VERSION_ =='1.7.0.0') {
                    $cart_presenter = new PrestaShop\PrestaShop\Adapter\Cart\CartPresenter();
                Media::addJsDef(array(
                    'cart' => $cart_presenter->present($this->context->cart)
                ));
            }
            //loads google map API
            if ($this->v17) {
                if (_PS_VERSION_!="1.7.0.0") {
                    $this->context->controller->registerJavascript(
                        'google-maps',
                        'https://maps.googleapis.com/maps/api/js?key='.Configuration::get('PAKKELABELS_GOOGLE_API_KEY'),
                        array(
                            'server' => 'remote',
                            'position' => 'bottom',
                            'priority' => 20
                        )
                    );
                } else {
                    $this->context->controller->addJS('https://maps.googleapis.com/maps/api/js?key='.Configuration::get('PAKKELABELS_GOOGLE_API_KEY'), 'all');
                }
                $this->context->controller->addCSS($this->_path . 'views/css/pakkelabels-17.css', 'all');
            } else {
                $this->context->controller->addJS('https://maps.googleapis.com/maps/api/js?key='.Configuration::get('PAKKELABELS_GOOGLE_API_KEY'), 'all');
                $this->context->controller->addCSS($this->_path . 'views/css/pakkelabels-16.css', 'all');
            }
            $this->context->controller->addCSS($this->_path . 'views/css/pakkelabel-modal.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/pakkelabel-modal.js', 'all');
        }

        if (!$this->v17) {
            // Specific for order 5 step page
            if ($page == 'order') {
                $cart = Context::getContext()->cart;
                if ($cart->id_address_delivery) {
                    $myAddress = new Address($cart->id_address_delivery);
                    Media::addJsDef(array(
                    'sDefaultZipcode' => $myAddress->postcode,
                    ));
                }
            }
            if ($page == 'order-opc') {
                if (Module::isInstalled('onepagecheckout') && Module::isEnabled('onepagecheckout')) {
                    $this->context->controller->addJS($this->_path . '/views/js/pakkelabels-order-onepagecheckout.js', 'all');
                } elseif (Module::isInstalled('onepagecheckoutps') && Module::isEnabled('onepagecheckoutps')) {
                    $this->context->controller->addJS($this->_path . '/views/js/pakkelabels-order-opcps.js', 'all');
                } else {
                    $this->context->controller->addJS($this->_path . '/views/js/pakkelabels-order-opc.js', 'all');
                }
            }
        }
        if ($page == 'order') {
            if ($this->v17) {
                if (_PS_VERSION_ =='1.7.0.0') {
                    $this->context->controller->addJS($this->_path . 'views/js/pakkelabels-order-170.js', 'all');
                } elseif(Module::isInstalled('onepagecheckoutps')) {
                    $this->context->controller->addJS($this->_path . 'views/js/pakkelabels-order-onepagecheckoutps-17.js', 'all');
                }else {
                    $this->context->controller->addJS($this->_path . 'views/js/pakkelabels-order-17.js', 'all');
                }
            } else {
                $this->context->controller->addJS($this->_path . 'views/js/pakkelabels-order-16.js', 'all');
            }
        }
        
        if ($page == 'module-supercheckout-supercheckout') {
            if ($this->v17) {
                $this->context->controller->addJS($this->_path . '/views/js/pakkelabels-supercheckout-17.js', 'all');
            } else {
                $this->context->controller->addJS($this->_path . '/views/js/pakkelabels-supercheckout-16.js', 'all');
            }
        }
    }
}
