<?php
/**
 *  @author    Shipmondo
 *  @copyright 2019 Shipmondo
 *  @license   All rights reserved
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Shipmondo extends CarrierModule
{
    const PREFIX = 'shipmondo_';
    protected $hooks = [
        'actionCarrierUpdate',
        'newOrder',
        'displayHeader',
        'header',
        'footer',
    ];
    protected $carriers = [
        'shipmondo_gls_service_point' => 'GLS PakkeShop',
        'shipmondo_gls_private' => 'GLS - Omdeling til privat',
        'shipmondo_gls_business' => 'GLS - Omdeling til erhverv',
        'shipmondo_postnord_service_point' => 'PostNord Valgfrit udleveringssted',
        'shipmondo_postnord_private' => 'PostNord - Omdeling til privat',
        'shipmondo_postnord_business' => 'PostNord - Omdeling til erhverv',
        'shipmondo_dao_service_point' => 'DAO Pakkeshop',
        'shipmondo_dao_direct' => 'DAO Direkte',
        'shipmondo_bring_service_point' => 'Bring - Valgfrit udleveringssted',
        'shipmondo_bring_private' => 'Bring - Aftenlevering til privat',
        'shipmondo_bring_business' => 'Bring - Omdeling til erhverv',
    ];

    private $validation_errors = [];

    public function __construct() {
        $this->name = 'shipmondo';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Shipmondo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Shipmondo');
        $this->description = $this->l('GLS, PostNord, DAO and Bring Shipping for PrestaShop');
    }

    public function hookNewOrder($params) {
        $carrier_id = $params['order']->id_carrier;

        $order = new Order((int) ($params['order']->id));

        $gls_carrier = Carrier::getCarrierByReference(
            Configuration::get('SHIPMONDO_GLS_CARRIER_ID')
        );

        $dao_carrier = Carrier::getCarrierByReference(
            Configuration::get('SHIPMONDO_DAO_CARRIER_ID')
        );

        $postnord_carrier = Carrier::getCarrierByReference(
            Configuration::get('SHIPMONDO_POSTNORD_CARRIER_ID')
        );

        $bring_carrier = Carrier::getCarrierByReference(
            Configuration::get('SHIPMONDO_BRING_CARRIER_ID')
        );

        $add_address = false;
        if ($gls_carrier->id == $carrier_id) {
            $alias = 'GLS';
            $add_address = true;
        }
        if ($dao_carrier->id == $carrier_id) {
            $alias = 'DAO';
            $add_address = true;
        }
        if ($postnord_carrier->id == $carrier_id) {
            $alias = 'PDK';
            $add_address = true;
        }
        if ($bring_carrier->id == $carrier_id) {
            $alias = 'Bring';
            $add_address = true;
        }

        if ($add_address) {
            if (isset($params['cart'])) {
                $sql = new DbQuery();
                $sql
                    ->select('service_point')
                    ->from('shipmondo_selected_service_points')
                    ->where('id_cart = '. (int) $params['cart']->id);
                $result = Db::getInstance()->getRow($sql);

                if ($result['service_point']) {
                    $service_point = Tools::jsonDecode($result['service_point']);

                    $id_address_max = (int) Db::getInstance()->getValue('SELECT MAX(`id_address`) FROM `' . _DB_PREFIX_ . 'address`');
                    $new_id = $id_address_max + 1;

                    $delivery_address = new Address($order->id_address_delivery);
                    $invoice_id = $order->id_address_invoice;
                    $service_point_address = clone $delivery_address;

                    $service_point_address->id = $new_id;
                    $service_point_address->company = $service_point->company;
                    $service_point_address->address1 = $service_point->address;
                    $service_point_address->address2 = $service_point->address2;
                    $service_point_address->postcode = $service_point->zip_code;
                    $service_point_address->city = $service_point->city;
                    $service_point_address->alias = $alias . ': ' . trim($service_point->address2);
                    $service_point_address->deleted = true; // address only used for this order
                    $service_point_address->active = true;

                    $order->id_address_delivery = $new_id;

                    // Update and/or add
                    $service_point_address->add();
                    $service_point_address->update();
                    $order->save();

                    // Update cart
                    $cart = Context::getContext()->cart;
                    $cart->updateAddressId($delivery_address->id, $new_id);
                    $cart->id_address_invoice = $invoice_id;
                    $cart->id_address_delivery = $new_id;
                    $cart->update();

                    $params['cart']->id_address_delivery = $new_id;
                    $params['cart']->id_address_invoice = $invoice_id;
                    $params['cart']->update();

                    $params['order']->id_address_delivery = $new_id;
                    $params['order']->id_address_invoice = $invoice_id;
                    $params['order']->update();
                }
            }
        }
    }

    public function getContent() {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            $frontend_key = (string) Tools::getValue('SHIPMONDO_FRONTEND_KEY');
            $google_api_key = (string) Tools::getValue('SHIPMONDO_GOOGLE_API_KEY');
            $gls_carrier_id = (string) Tools::getValue('SHIPMONDO_GLS_CARRIER_ID');
            $dao_carrier_id = (string) Tools::getValue('SHIPMODNO_DAO_CARRIER_ID');
            $postnord_carrier_id = (string) Tools::getValue('SHIPMONDO_POSTNORD_CARRIER_ID');
            $bring_carrier_id = (string) Tools::getValue('SHIPMONDO_BRING_CARRIER_ID');
            $frontend_type = (string) Tools::getValue('SHIPMONDO_FRONTEND_TYPE');

            $validation_error_title = $this->l('The Shipmondo shipping module, requires all the settings below to be entered correctly and saved before the module will operate correctly.') . '</br>';
            $validation_error_title .= $this->l('Invalid Configuration value(s), please insert the following:');
            $validation_errors = [];
            $valid = true;
            
            Configuration::updateValue('SHIPMONDO_FRONTEND_TYPE', $frontend_type);
            
            $frontend_key_error_html =
                '<a target="_blank" href="https://app.shipmondo.com/main/app/#/setting/api">' .
                    $this->l('Frontend Key') .
                '</a>';
            
            $valid &= $this->validateAndUpdateValue(
                $frontend_key,
                'SHIPMONDO_FRONTEND_KEY',
                $frontend_key_error_html
            );

            $google_error_html = 
                '<a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">'.
                    $this->l('Google Map API Key') .
                '</a>';

            $valid &= $this->validateAndUpdateValue(
                $google_api_key,
                'SHIPMONDO_GOOGLE_API_KEY',
                $google_error_html
            );


            $valid &= $this->validateAndUpdateValue(
                $gls_carrier_id,
                'SHIPMONDO_GLS_CARRIER_ID',
                $this->l('GLS Carrier ID')
            );

            $valid &= $this->validateAndUpdateValue(
                $dao_carrier_id,
                'SHIPMONDO_DAO_CARRIER_ID',
                $this->l('DAO Carrier ID')
            );

            $valid &= $this->validateAndUpdateValue(
                $postnord_carrier_id,
                'SHIPMONDO_POSTNORD_CARRIER_ID',
                $this->l('PostNord Carrier ID')
            );

            $valid &= $this->validateAndUpdateValue(
                $bring_carrier_id,
                'SHIPMONDO_BRING_CARRIER_ID',
                $this->l('Bring Carrier ID')
            );

            if (!$valid) {
                foreach ($this->validation_errors as $key)
                    $validation_error_title .= '<li class="test">' . $key . '</li>';
                $output .= $this->displayError($validation_error_title);
            }
        }

        return $output . $this->displayForm();
    }

    public function displayForm() {
        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $all_carriers = Carrier::getCarriers($default_lang, false, false, false, null, ALL_CARRIERS);
        $carriers = [];

        foreach ($all_carriers as $carrier) {
            $carriers[] = [
                'id_option' => $carrier['id_reference'],
                'name' => $carrier['name'],
            ];
        }
        $fields_form = [];

        $prestashop_guide_url = 'https://kundecenter.pakkelabels.dk/' . $default_lang . '/articles/2027196-prestashop-1-7-opsaetning-af-shipmondo-fragtmodul';
        $thirty_bees_guide_url = 'https://kundecenter.pakkelabels.dk/' . $default_lang . '/articles/2027426-thirty-bees-opsaetning-af-shipmondo-fragtmodul';

        // Init Fields form
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'name' => 'SHIPMONDO_DESC',
                    'type' => 'html',
                    'html_content' =>
                        $this->l('Follow the setup guide for PrestaShop') . ':
                        <a href="' . $prestashop_guide_url .'" target="_blank">' .
                            $prestashop_guide_url .
                        '</a>',
                ],
                [
                    'name' => 'SHIPMONDO_DESC_BEES',
                    'type' => 'html',
                    'html_content' =>
                        $this->l('Follow the setup guide for thirty bees') . ':
                        <a href="' . $thirty_bees_guide_url .'" target="_blank">' .
                            $thirty_bees_guide_url .
                        '</a>',
                ],
                [
                    'name' => 'SHIPMONDO_FRONTEND_KEY',
                    'type' => 'text',
                    'label' => $this->l('Frontend Key'),
                    'desc' =>
                        $this->l('Insert Frontend Key here - Get the key from') . ': 
                        <a target="_blank" href="https://app.shipmondo.comk/main/app/#/setting/api">
                            Shipmondo
                        </a>',
                    'required' => true,
                    'col' => 4,
                ],
                [
                    'name' => 'SHIPMONDO_GOOGLE_API_KEY',
                    'type' => 'text',
                    'label' => $this->l('Google API Map Key'),
                    'desc' =>
                        $this->l('Insert Google API Map Key - Get the key from') . ': 
                        <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">
                            Google
                        </a>',
                    'required' => true,
                    'col' => 4,
                ],
                [
                    'name' => 'SHIPMONDO_GLS_CARRIER_ID',
                    'type' => 'select',
                    'options' => [
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                    'label' => $this->l('GLS Packetshop'),
                    'required' => true,
                ],
                [
                    'name' => 'SHIPMONDO_POSTNORD_CARRIER_ID',
                    'type' => 'select',
                    'options' => [
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                    'label' => $this->l('PostNord choosen pickuppoint'),
                    'required' => true,
                ],
                [
                    'name' => 'SHIPMONDO_DAO_CARRIER_ID',
                    'type' => 'select',
                    'options' => [
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                    'label' => $this->l('DAO Packetshop'),
                    'required' => true,
                ],
                [
                    'name' => 'SHIPMONDO_BRING_CARRIER_ID',
                    'type' => 'select',
                    'options' => [
                        'query' => $carriers,
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                    'label' => $this->l('Bring choosen pickuppoint'),
                    'required' => true,
                ],
                [
                    'name' => 'SHIPMONDO_FRONTEND_TYPE',
                    'type' => 'radio',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 'popup',
                            'label' => $this->l('Popup'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 'dropdown',
                            'label' => $this->l('Dropdown'),
                        ],
                        [
                            'id' => 'active_radio',
                            'value' => 'radio',
                            'label' => $this->l('Radio Button'),
                        ],
                    ],
                    'label' => $this->l('Select display on checkout'),
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ]
        ];

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
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ],
        ];

        // Load current value
        $helper->fields_value['SHIPMONDO_FRONTEND_KEY'] = Configuration::get('SHIPMONDO_FRONTEND_KEY');
        $helper->fields_value['SHIPMONDO_GOOGLE_API_KEY'] = Configuration::get('SHIPMONDO_GOOGLE_API_KEY');
        $helper->fields_value['SHIPMONDO_GLS_CARRIER_ID'] = Configuration::get('SHIPMONDO_GLS_CARRIER_ID');
        $helper->fields_value['SHIPMONDO_POSTNORD_CARRIER_ID'] = Configuration::get('SHIPMONDO_POSTNORD_CARRIER_ID');
        $helper->fields_value['SHIPMONDO_DAO_CARRIER_ID'] = Configuration::get('SHIPMONDO_DAO_CARRIER_ID');
        $helper->fields_value['SHIPMONDO_BRING_CARRIER_ID'] = Configuration::get('SHIPMONDO_BRING_CARRIER_ID');
        if (Configuration::get('SHIPMONDO_FRONTEND_TYPE') != '') {
            $helper->fields_value['SHIPMONDO_FRONTEND_TYPE'] = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
        } else {
            $helper->fields_value['SHIPMONDO_FRONTEND_TYPE'] = 'popup';
        }

        return $helper->generateForm($fields_form);
    }

    public function install() {
        if (parent::install()) {
            foreach ($this->hooks as $hook) {
                if (!$this->registerHook($hook))
                    return false;
            }

            if (!$this->createCarriers())
                return false;

            if (!$this->createDatabaseTables())
                return false;

            return true;
        }

        return false;
    }

    public function uninstall() {
        if (parent::uninstall()) {
            foreach ($this->hooks as $hook) {
                if (!$this->unregisterHook($hook))
                    return false;
            }

            if (!$this->deleteCarriers())
                return false;

            if (!$this->deleteSettings())
                return false;

            if (!$this->deleteDatabaseTables())
                return false;

            return true;
        }

        return false;
    }

    public function getOrderShippingCost($params, $shipping_cost) {
        return $shipping_cost;
    }

    public function getOrderShippingCostExternal($params) {
        return $this->getOrderShippingCost($params, 0);
    }

    public function hookActionCarrierUpdate($params) {
        if ($params['carrier']->id_reference == Configuration::get(self::PREFIX . 'swipbox_reference')) {
            Configuration::updateValue(self::PREFIX . 'swipbox', $params['carrier']->id);
        }
    }

    public function hookDisplayHeader($params) {
        $context = $this->context->controller;
        // Get shipping method id from id reference
        $gls = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_GLS_CARRIER_ID'));
        $dao = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_DAO_CARRIER_ID'));
        $pdk = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_POSTNORD_CARRIER_ID'));
        $bring = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_BRING_CARRIER_ID'));

        $page = $context->php_self;

        if (!$page)
            $page = $context->page_name;

        $cid = $params['cookie']->id_customer;

        $customer = new Customer($cid);
        $customer_address = $customer->getAddresses(1);
        $postcode = $customer_address[0]['postcode'];

        if ($page == 'order') {
            Media::addJsDef([
                'findServicePointText' => $this->l('Find nearest pickup point'),
                'zipCodeFieldText' => $this->l('Zipcode'),
                'addressFieldText' => $this->l('Address'),
                'modalHeaderTitle' => $this->l('Choose pickup point'),
                //TODO GOT TO HERE
                'sPakkelabel_open_map' => $this->l('Show Map'),
                'sPakkelabel_hide_map' => $this->l('Hide Map'),
                'sChoose_stop_btn' => $this->l('Choose'),
                'gls_carrier_id' => $gls->id,
                'dao_carrier_id' => $dao->id,
                'iPakkelabels_ID_POSTNORD' => $pdk->id,
                'bring_carrier_id' => $bring->id,
                'iPakkelabels_ID_WINDOW' => Configuration::get('SHIPMONDO_FRONTEND_TYPE'),
                'selected_shop_header' => $this->l('Currently choose pickup point:'),
                'error_message_zipcode' => $this->l('The zip code must be 4 digit long, and numeric'),
                'error_no_cords_found' => $this->l('* Could not mark this pickup point on the map'),
                'dataRoot' => Tools::getProtocol(Tools::usingSecureMode()) . $_SERVER['HTTP_HOST'] . $this->getPathUri(),
                'error_no_shop_selected' => $this->l('You must choose a pickup point before, you can proceed'),
                'error_login_before' => $this->l('Before proceed, you must either login or use guest checkout'),
                'service_points_endpoint' => Context::getContext()->link->getModuleLink('pakkelabels_shipping', 'servicepoints')
            ]);
            //loads Google map API
            $context->registerJavascript(
                        'google-maps',
                        'https://maps.googleapis.com/maps/api/js?key=' . Configuration::get('SHIPMONDO_GOOGLE_API_KEY'),
                        [
                            'server' => 'remote',
                            'position' => 'bottom',
                            'priority' => 20,
                        ]
                    );
            $context->addCSS($this->_path . 'views/css/shipmondo-modal.css', 'all');
            $context->addCSS($this->_path . 'views/css/shipmondo.css', 'all');
            $context->addJS($this->_path . 'views/js/shipmondo.js', 'all');
            $context->addJS($this->_path . 'views/js/shipmondo-modal.js', 'all');
        }
    }

    protected function createCarriers() {
        foreach ($this->carriers as $key => $value) {
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
                    $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'carrier_group WHERE
                    id_carrier = ' . (int) $carrier->id . ' AND id_group = ' . (int) $group['id_group'];
                    $group_exist = Db::getInstance()->getValue($sql, false);
                    if (!$group_exist) {
                        Db::getInstance()->insert('carrier_group', [
                            'id_carrier' => (int) $carrier->id,
                            'id_group' => (int) $group['id_group'],
                        ], false, false);
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
                    $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'delivery WHERE
                    id_carrier = ' . (int) $carrier->id . ' AND id_zone = ' . (int) $z['id_zone'];
                    $price_range_exist = Db::getInstance()->getValue($sql, false);

                    if (!$price_range_exist) {
                        $id_carrier = (int) $carrier->id;
                        $id_zone = (int) $z['id_zone'];
                        $range_id = (int) $rangePrice->id;
                        $rangewt_id = (int) $rangeWeight->id;
                        Db::getInstance()->insert('carrier_zone', [
                            'id_carrier' => $id_carrier,
                            'id_zone' => $id_zone,
                        ], false, false);
                        Db::getInstance()->insert('delivery', [
                            'id_carrier' => $id_carrier,
                            'id_range_price' => $range_id,
                            'id_range_weight' => null,
                            'id_zone' => $id_zone,
                            'price' => '0',
                        ], true, false);

                        Db::getInstance()->insert('delivery', [
                            'id_carrier' => $id_carrier,
                            'id_range_price' => null,
                            'id_range_weight' => $rangewt_id,
                            'id_zone' => $id_zone,
                            'price' => '0',
                        ], true, false);
                    }
                }

                copy(__DIR__ . '/views/img/' . $key . '.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg'); //assign carrier logo

                Configuration::updateValue(self::PREFIX . $key, $carrier->id);
                Configuration::updateValue(self::PREFIX . $key . '_reference', $carrier->id);

                if ($key == 'pakkelabels_GLS') {
                    Configuration::updateValue('SHIPMONDO_GLS_CARRIER_ID', $carrier->id);
                } elseif ($key == 'pakkelabels_PostNord') {
                    Configuration::updateValue('SHIPMONDO_POSTNORD_CARRIER_ID', $carrier->id);
                } elseif ($key == 'pakkelabels_DAO') {
                    Configuration::updateValue('SHIPMONDO_DAO_CARRIER_ID', $carrier->id);
                } elseif ($key == 'pakkelabels_bring') {
                    Configuration::updateValue('SHIPMONDO_BRING_CARRIER_ID', $carrier->id);
                }
            }
        }

        return true;
    }

    protected function createDatabaseTables() {
        $sql_carts = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pakkelabel_carts` ('
            . '`id_pkl_cart` int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . '`id_cart` int(10), '
            . '`shop_data` text, '
            . '`id_carrier` int(10) '
        . ')';
        $db_instance = DB::getInstance();

        return $db_instance->Execute($sql_carts);
    }

    protected function deleteDatabaseTables() {
        $sql_carts = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pakkelabel_carts`';
        $db_instance = DB::getInstance();

        return $db_instance->Execute($sql_carts);
    }

    protected function deleteCarriers() {
        $keys = array_keys($this->carriers);
        foreach ($keys as $key) {
            $tmp_carrier_id = Configuration::get(self::PREFIX . $key);
            $carrier = new Carrier($tmp_carrier_id);
            $carrier->delete();
        }

        return true;
    }

    protected function deleteSettings() {
        Configuration::deleteByName('SHIPMONDO_GLS_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_POSTNORD_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_DAO_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_BRING_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_FRONTEND_TYPE');

        return true;
    }

    private function validateAndUpdateValue($value, $value_key, $error_message) {
        if (empty($value) || !Validate::isGenericName($value)) {
            $this->validation_errors[] = $error_message;
            Configuration::updateValue($value_key, '');
            return false;
        }
        
        Configuration::updateValue($value_key, $value);
        return true;
    }
}
