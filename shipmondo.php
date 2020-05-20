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
        'gls_service_point' => 'GLS PakkeShop',
        'gls_private' => 'GLS - Omdeling til privat',
        'gls_business' => 'GLS - Omdeling til erhverv',
        'postnord_service_point' => 'PostNord Valgfrit udleveringssted',
        'postnord_private' => 'PostNord - Omdeling til privat',
        'postnord_business' => 'PostNord - Omdeling til erhverv',
        'dao_service_point' => 'dao Pakkeshop',
        'dao_direct' => 'dao Direkte',
        'bring_service_point' => 'Bring - Valgfrit udleveringssted',
        'bring_private' => 'Bring - Aftenlevering til privat',
        'bring_business' => 'Bring - Omdeling til erhverv',
    ];

    private $validation_errors = [];

    public function __construct()
    {
        $this->name = 'shipmondo';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.5';
        $this->author = 'Shipmondo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => _PS_VERSION_,
        ];
        $this->displayName = $this->l('Shipmondo');
        $this->description = $this->l('GLS, PostNord, dao and Bring Shipping for PrestaShop');
    }

    public function hookNewOrder($params)
    {
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
        if ($gls_carrier->id === $carrier_id) {
            $alias = 'GLS';
            $add_address = true;
        }
        if ($dao_carrier->id === $carrier_id) {
            $alias = 'DAO';
            $add_address = true;
        }
        if ($postnord_carrier->id === $carrier_id) {
            $alias = 'PDK';
            $add_address = true;
        }
        if ($bring_carrier->id === $carrier_id) {
            $alias = 'Bring';
            $add_address = true;
        }

        if ($add_address) {
            if (isset($params['cart'])) {
                $sql = new DbQuery();
                $sql
                    ->select('service_point')
                    ->from('shipmondo_selected_service_points')
                    ->where('id_cart = ' . (int) $params['cart']->id);
                $result = Db::getInstance()->getRow($sql);

                if ($result['service_point']) {
                    $service_point = Tools::jsonDecode($result['service_point']);

                    $delivery_address = new Address($order->id_address_delivery);
                    $invoice_id = $order->id_address_invoice;

                    // Create address for service point
                    $service_point_address = clone $delivery_address;
                    $service_point_address->id = null;
                    $service_point_address->company = $service_point->company_name;
                    $service_point_address->address1 = $service_point->address;
                    $service_point_address->address2 = $service_point->address2;
                    $service_point_address->postcode = $service_point->zip_code;
                    $service_point_address->city = $service_point->city;
                    $service_point_address->alias = $alias . ': ' . trim($service_point->address2);
                    $service_point_address->deleted = true; // Address only used for this order
                    $service_point_address->active = true;
                    $service_point_address->save();
                    
                    $new_id = $service_point_address->id;

                    // Update delivery adress to be service point address
                    $order->id_address_delivery = $new_id;
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

    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            $frontend_key = (string) Tools::getValue('SHIPMONDO_FRONTEND_KEY');
            $google_api_key = (string) Tools::getValue('SHIPMONDO_GOOGLE_API_KEY');
            $gls_carrier_id = (string) Tools::getValue('SHIPMONDO_GLS_CARRIER_ID');
            $dao_carrier_id = (string) Tools::getValue('SHIPMONDO_DAO_CARRIER_ID');
            $postnord_carrier_id = (string) Tools::getValue('SHIPMONDO_POSTNORD_CARRIER_ID');
            $bring_carrier_id = (string) Tools::getValue('SHIPMONDO_BRING_CARRIER_ID');
            $frontend_type = (string) Tools::getValue('SHIPMONDO_FRONTEND_TYPE');

            $validation_error_title = $this->l('Please fill out all required fields.') . '<br>';
            $validation_error_title .= $this->l('Invalid configuration, please check:');
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
                '<a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">' .
                    $this->l('Google map API key') .
                '</a>';

            $valid &= $this->validateAndUpdateValue(
                $google_api_key,
                'SHIPMONDO_GOOGLE_API_KEY',
                $google_error_html
            );

            $valid &= $this->validateAndUpdateValue(
                $gls_carrier_id,
                'SHIPMONDO_GLS_CARRIER_ID',
                $this->l('GLS carrier ID')
            );

            $valid &= $this->validateAndUpdateValue(
                $dao_carrier_id,
                'SHIPMONDO_DAO_CARRIER_ID',
                $this->l('dao carrier ID')
            );

            $valid &= $this->validateAndUpdateValue(
                $postnord_carrier_id,
                'SHIPMONDO_POSTNORD_CARRIER_ID',
                $this->l('PostNord carrier ID')
            );

            $valid &= $this->validateAndUpdateValue(
                $bring_carrier_id,
                'SHIPMONDO_BRING_CARRIER_ID',
                $this->l('Bring carrier ID')
            );

            if (!$valid) {
                foreach ($this->validation_errors as $key) {
                    $validation_error_title .= '<li class="test">' . $key . '</li>';
                }
                $output .= $this->displayError($validation_error_title);
            }
        }

        # Check if database table still exists
        $output .= $this->checkDatabaseTableExists();

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = $this->context->language->id;
        $all_carriers = Carrier::getCarriers($default_lang, false, false, false, null, ALL_CARRIERS);
        $carriers = [];

        foreach ($all_carriers as $carrier) {
            $carriers[] = [
                'id_option' => $carrier['id_reference'],
                'name' => $carrier['name'],
            ];
        }
        $fields_form = [];

        $prestashop_guide_url = 'https://kundecenter.pakkelabels.dk/da/articles/2027196-prestashop-1-7-opsaetning-af-shipmondo-fragtmodul';

        // Init fields form
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'name' => 'SHIPMONDO_DESC',
                    'type' => 'html',
                    'html_content' => $this->l('Follow the setup guide') . ' :' .
                        ' <a href="' . $prestashop_guide_url . '" target="_blank">' .
                            $this->l('PrestaShop guide') .
                        '</a>',
                ],
                [
                    'name' => 'SHIPMONDO_FRONTEND_KEY',
                    'type' => 'text',
                    'label' => $this->l('Shipping module API key'),
                    'desc' => $this->l('Insert your shipping module API key here. You can generate a key from') . ': 
                        <a target="_blank" href="https://app.shipmondo.com/main/app/#/setting/api">
                            Shipmondo
                        </a>',
                    'required' => true,
                    'col' => 4,
                ],
                [
                    'name' => 'SHIPMONDO_GOOGLE_API_KEY',
                    'type' => 'text',
                    'label' => $this->l('Google API Map Key'),
                    'desc' => $this->l('Insert your Google API key here. You can generate a key from') . ': 
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
                    'label' => $this->l('GLS pickup point'),
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
                    'label' => $this->l('PostNord chosen pickup point'),
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
                    'label' => $this->l('dao pickup point'),
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
                    'label' => $this->l('Bring chosen pickup point'),
                    'required' => true,
                ],
                [
                    'name' => 'SHIPMONDO_FRONTEND_TYPE',
                    'type' => 'radio',
                    'values' => [
                        [
                            'id' => 'option_popup',
                            'value' => 'popup',
                            'label' => $this->l('Popup'),
                        ],
                        [
                            'id' => 'option_dropdown',
                            'value' => 'dropdown',
                            'label' => $this->l('Dropdown'),
                        ],
                        [
                            'id' => 'option_radio',
                            'value' => 'radio',
                            'label' => $this->l('Radio button'),
                        ],
                    ],
                    'label' => $this->l('Display on checkout'),
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        // Toolbar and button
        $helper->show_toolbar = false;
        $helper->submit_action = 'submit' . $this->name;

        // Load current value
        $helper->fields_value['SHIPMONDO_FRONTEND_KEY'] = Configuration::get('SHIPMONDO_FRONTEND_KEY');
        $helper->fields_value['SHIPMONDO_GOOGLE_API_KEY'] = Configuration::get('SHIPMONDO_GOOGLE_API_KEY');
        $helper->fields_value['SHIPMONDO_GLS_CARRIER_ID'] = Configuration::get('SHIPMONDO_GLS_CARRIER_ID');
        $helper->fields_value['SHIPMONDO_POSTNORD_CARRIER_ID'] = Configuration::get('SHIPMONDO_POSTNORD_CARRIER_ID');
        $helper->fields_value['SHIPMONDO_DAO_CARRIER_ID'] = Configuration::get('SHIPMONDO_DAO_CARRIER_ID');
        $helper->fields_value['SHIPMONDO_BRING_CARRIER_ID'] = Configuration::get('SHIPMONDO_BRING_CARRIER_ID');
        $helper->fields_value['SHIPMONDO_FRONTEND_TYPE'] = Configuration::get('SHIPMONDO_FRONTEND_TYPE');

        return $helper->generateForm($fields_form);
    }

    public function install()
    {
        if (parent::install()) {
            foreach ($this->hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }

            $old_module_name = 'pakkelabels_shipping';
            if (Module::isInstalled($old_module_name)) {
                $this->migrateFromPakkelabels();
                Module::disableByName($old_module_name);
            }

            if (!$this->createCarriers()) {
                return false;
            }

            if (!$this->createDatabaseTables()) {
                return false;
            }

            $this->setDefaultFrontendType();

            return true;
        }

        return false;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            foreach ($this->hooks as $hook) {
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
        if ($params['carrier']->id_reference === Configuration::get(self::PREFIX . 'swipbox_reference')) {
            Configuration::updateValue(self::PREFIX . 'swipbox', $params['carrier']->id);
        }
    }

    public function hookDisplayHeader($params)
    {
        $context = $this->context->controller;
        // Get shipping method id from id reference
        $gls = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_GLS_CARRIER_ID'));
        $dao = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_DAO_CARRIER_ID'));
        $pdk = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_POSTNORD_CARRIER_ID'));
        $bring = Carrier::getCarrierByReference(Configuration::get('SHIPMONDO_BRING_CARRIER_ID'));

        $current_page = Tools::getValue('controller');



        $order_pages = [
            'order', //default PS
            'supercheckout' //Knowband
        ];

        if (in_array($current_page, $order_pages)) {
            Media::addJsDef([
                'choose_pickup_point_text' => $this->l('Choose pickup point'),
                'gls_carrier_id' => $gls->id,
                'dao_carrier_id' => $dao->id,
                'postnord_carrier_id' => $pdk->id,
                'bring_carrier_id' => $bring->id,
                'frontend_type' => Configuration::get('SHIPMONDO_FRONTEND_TYPE'),
                'selection_button_html' => $this->fetch('module:shipmondo/views/templates/front/' . Configuration::get('SHIPMONDO_FRONTEND_TYPE') . '/selection_button.tpl'),
                'modal_html' => $this->fetch('module:shipmondo/views/templates/front/popup/modal.tpl'),
                'module_base_url' => Tools::getProtocol(Tools::usingSecureMode()) . $_SERVER['HTTP_HOST'] . $this->getPathUri(),
                'service_points_endpoint' => Context::getContext()->link->getModuleLink('shipmondo', 'servicepoints'),
                'extentions_endpoint' => Context::getContext()->link->getModuleLink('shipmondo', 'extensions')
            ]);
            if (Configuration::get('SHIPMONDO_FRONTEND_TYPE') === 'popup') {
                // Loads Google map API
                $context->registerJavascript(
                    'google-maps',
                    'https://maps.googleapis.com/maps/api/js?key=' . Configuration::get('SHIPMONDO_GOOGLE_API_KEY'),
                    [
                        'server' => 'remote',
                        'position' => 'bottom',
                        'priority' => 20,
                    ]
                );
            }
            $context->addCSS($this->_path . 'views/css/shipmondo.css', 'all');

            // Add theme overrides to views/css/theme.
            $themes = [
                // Add themes into this array
                'warehouse'
            ];

            if (in_array(_THEME_NAME_, $themes)) {
                $context->addCSS($this->_path . 'views/css/theme/' . _THEME_NAME_ . '.css', 'all');
            }

            // Add module overrides to views/css/module.
            $modules = [
                // Add modules into this array
                'onepagecheckoutps', //Prestateam - Tested with v1.0.3
                'supercheckout', //Knowband - Tested with v4.0.6,
                'thecheckout' // Prestamodules / Zelarg - v3.2.5
            ];
            foreach ($modules as $module) {
                if (Module::isInstalled($module) && Module::isEnabled($module)) {
                    $context->addCSS($this->_path . 'views/css/module/' . $module . '.css', 'all');
                    $context->addJS($this->_path . 'views/js/module/' . $module . '.js', 'all');
                }
            }

            $context->addJS($this->_path . 'views/js/shipmondo.js', 'all');
        }
    }

    protected function createCarriers()
    {
        foreach ($this->carriers as $key => $value) {
            $carrier = Carrier::getCarrierByReference(
                Configuration::get(self::PREFIX . $key)
            );

            if (!isset($carrier) || $carrier->id <= 0 || $carrier->deleted) {
                // Create new carrier
                $carrier = $this->createCarrier($key, $value);
            }

            if ($carrier->id <= 0) {
                return false;
            }

            // The first part of the key is name of logo
            $logo_name = implode('_', array_slice(explode('_', $key), 0, 1));

            // Assign/overwrite carrier logo
            copy(_PS_MODULE_DIR_ . 'shipmondo/views/img/carrier_logos/' . $logo_name . '.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg');
        }
        return true;
    }

    protected function createDatabaseTables()
    {
        $sql_carts = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_selected_service_points` ('
            . '`id_smd_service_point` int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . '`id_cart` int(10), '
            . '`service_point` text, '
            . '`id_carrier` int(10) '
        . ')';
        $db_instance = DB::getInstance();

        return $db_instance->Execute($sql_carts);
    }

    // If frontend type is not set, set as popup
    protected function setDefaultFrontendType()
    {
        $frontend_type = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
        if (empty($frontend_type)) {
            Configuration::updateValue('SHIPMONDO_FRONTEND_TYPE', 'popup');
        }
    }

    protected function deleteDatabaseTables()
    {
        $sql_carts = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'shipmondo_selected_service_points`';
        $db_instance = DB::getInstance();

        return $db_instance->Execute($sql_carts);
    }

    protected function deleteCarriers()
    {
        $keys = array_keys($this->carriers);
        foreach ($keys as $key) {
            $carrier_id = Configuration::get(self::PREFIX . $key);
            $carrier = new Carrier($carrier_id);
            $carrier->delete();
            Configuration::deleteByName(self::PREFIX . $key);
        }

        return true;
    }

    protected function deleteSettings()
    {
        Configuration::deleteByName('SHIPMONDO_FRONTEND_KEY');
        Configuration::deleteByName('SHIPMONDO_GLS_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_POSTNORD_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_DAO_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_BRING_CARRIER_ID');
        Configuration::deleteByName('SHIPMONDO_FRONTEND_TYPE');

        return true;
    }

    private function createCarrier($reference, $name)
    {
        $carrier = new Carrier();
        $carrier->name = $name;
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
                // Check if values exist before insert
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

            $range_price = new RangePrice();
            $range_price->id_carrier = $carrier->id;
            $range_price->delimiter1 = '0';
            $range_price->delimiter2 = '1000000';
            $range_price->add();

            $range_weight = new RangeWeight();
            $range_weight->id_carrier = $carrier->id;
            $range_weight->delimiter1 = '0';
            $range_weight->delimiter2 = '1000000';
            $range_weight->add();

            $zones = Zone::getZones(true);
            foreach ($zones as $z) {
                // Check if values exist before insert
                $sql =
                    'SELECT COUNT(*) ' .
                    'FROM ' . _DB_PREFIX_ . 'delivery ' .
                    'WHERE id_carrier = ' . (int) $carrier->id .
                    ' AND id_zone = ' . (int) $z['id_zone'];

                $price_range_exist = Db::getInstance()->getValue($sql, false);

                if (!$price_range_exist) {
                    $id_carrier = (int) $carrier->id;
                    $id_zone = (int) $z['id_zone'];
                    $range_id = (int) $range_price->id;
                    $range_weight_id = (int) $range_weight->id;
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
                        'id_range_weight' => $range_weight_id,
                        'id_zone' => $id_zone,
                        'price' => '0',
                    ], true, false);
                }
            }

            Configuration::updateValue(self::PREFIX . $reference, $carrier->id);

            switch ($reference) {
                case 'gls_service_point':
                    Configuration::updateValue('SHIPMONDO_GLS_CARRIER_ID', $carrier->id);
                    break;
                case 'postnord_service_point':
                    Configuration::updateValue('SHIPMONDO_POSTNORD_CARRIER_ID', $carrier->id);
                    break;
                case 'dao_service_point':
                    Configuration::updateValue('SHIPMONDO_DAO_CARRIER_ID', $carrier->id);
                    break;
                case 'bring_service_point':
                    Configuration::updateValue('SHIPMONDO_BRING_CARRIER_ID', $carrier->id);
                    break;
            }

            return $carrier;
        }

        return null;
    }

    private function validateAndUpdateValue($value, $value_key, $error_message)
    {
        if (empty($value) || !Validate::isGenericName($value)) {
            $this->validation_errors[] = $error_message;
            Configuration::updateValue($value_key, '');

            return false;
        }

        Configuration::updateValue($value_key, $value);

        return true;
    }

    private function migrateFromPakkelabels()
    {
        $pkl_carrier_keys = [
            'gls_service_point' => 'pakkelabels_GLS',
            'gls_private' => 'pakkelabels_GLS_private',
            'gls_business' => 'pakkelabels_GLS_business',
            'postnord_service_point' => 'pakkelabels_PostNord',
            'postnord_private' => 'pakkelabels_PostNord_private',
            'postnord_business' => 'pakkelabels_PostNord_business',
            'dao_service_point' => 'pakkelabels_DAO',
            'dao_direct' => 'pakkelabels_dao_direct',
            'bring_service_point' => 'pakkelabels_bring',
            'bring_private' => 'pakkelabels_bring_private',
            'bring_business' => 'pakkelabels_bring_business',
        ];

        foreach (array_keys($this->carriers) as $key) {
            $pkl_key = 'pakkelabels_shipping_' . $pkl_carrier_keys[$key];
            $value = Configuration::get($pkl_key);
            if (isset($value)) {
                $carrier = Carrier::getCarrierByReference($value);
                $carrier->external_module_name = $this->name;
                $carrier->update();

                Configuration::updateValue(self::PREFIX . $key, $value);
                Configuration::deleteByName($pkl_key);
            }
        }

        $pkl_config_keys = [
            'SHIPMONDO_FRONTEND_KEY' => 'PAKKELABELS_SHIPPING_FRONTEND_KEY',
            'SHIPMONDO_GOOGLE_API_KEY' => 'PAKKELABELS_GOOGLE_API_KEY',
            'SHIPMONDO_GLS_CARRIER_ID' => 'PAKKELABELS_SHIPPING_ID_GLS',
            'SHIPMONDO_POSTNORD_CARRIER_ID' => 'PAKKELABELS_SHIPPING_ID_POSTNORD',
            'SHIPMONDO_DAO_CARRIER_ID' => 'PAKKELABELS_SHIPPING_ID_DAO',
            'SHIPMONDO_BRING_CARRIER_ID' => 'PAKKELABELS_SHIPPING_ID_BRING',
            'SHIPMONDO_FRONTEND_TYPE' => 'PAKKELABELS_FRONT_OPTION',
        ];

        foreach ($pkl_config_keys as $smd_key => $pkl_key) {
            $value = Configuration::get($pkl_key);

            if (isset($value)) {
                if ($smd_key == 'SHIPMONDO_FRONTEND_TYPE') {
                    $value = Tools::strtolower($value); // fix frontend type
                }

                Configuration::updateValue($smd_key, $value);
                Configuration::deleteByName($pkl_key);
            }
        }
    }

    private function checkDatabaseTableExists()
    {
        $db_instance = Db::getInstance();
        $table_name =  _DB_PREFIX_ . 'shipmondo_selected_service_points';

        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '{$table_name}'";
        $table_exists = !empty($db_instance->getValue($sql, false));

        if(!$table_exists) {
            $this->createDatabaseTables();
            $table_exists = !empty($db_instance->getValue($sql, false));

            if($table_exists) {
                return $this->displayConfirmation($this->l('Database table "') . $table_name . $this->l('" didn\'t exists, but it was possible to create.'));
            } else {
                return $this->displayError($this->l('Database table "') . $table_name . $this->l('" does not exists and it was not possible to create.'));
            }
        }       

        return '';
    }
}
