<?php
/**
 *  @author    Shipmondo
 *  @copyright 2023 Shipmondo
 *  @license   All rights reserved
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Shipmondo\Controller\Admin\ShipmondoCarrierController;
use Shipmondo\Entity\ShipmondoCarrier;
use Shipmondo\Entity\ShipmondoServicePoint;

class Shipmondo extends CarrierModule
{
    const PREFIX = 'shipmondo_';

    protected $hooks = [
        'displayAdminOrderSide',
        'displayHeader',
        'displayCarrierExtraContent',
        'newOrder'
    ];

    protected $tables = [
        'selected_service_points',
        'carrier',
        'service_point'
    ];

    private $validation_errors = [];

    public function __construct()
    {
        $this->name = 'shipmondo';
        $this->tab = 'shipping_logistics';
        $this->version = '2.0.0';
        $this->author = 'Shipmondo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => '8.99.99',
        ];
        $this->displayName = "Shipmondo";
        $this->description = $this->trans('A complete shipping solution for PrestaShop', [], 'Modules.Shipmondo.Admin'); # TODO description

        $this->tabs = [
            [
                'name' => 'Shipmondo',
                'class_name' => ShipmondoCarrierController::TAB_CLASS_NAME,
                'route_name' => 'ps_controller_shipmondo_shipmondo_carriers',
                'visible' => true,
                'parent_class_name' => 'AdminParentShipping',
            ],
        ];
    }

    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            $frontend_key = (string) Tools::getValue('SHIPMONDO_FRONTEND_KEY');
            $google_api_key = (string) Tools::getValue('SHIPMONDO_GOOGLE_API_KEY');
            $frontend_type = (string) Tools::getValue('SHIPMONDO_FRONTEND_TYPE');

            $validation_error_title = $this->trans('Please fill out all required fields.', [], 'Modules.Shipmondo.Admin') . '<br>';
            $validation_error_title .= $this->trans('Invalid configuration, please check:', [], 'Modules.Shipmondo.Admin');
            $valid = true;

            Configuration::updateValue('SHIPMONDO_FRONTEND_TYPE', $frontend_type);

            $frontend_key_error_html =
                '<a target="_blank" href="https://app.shipmondo.com/main/app/#/setting/api">' .
                $this->trans('Frontend Key', [], 'Modules.Shipmondo.Admin') .
                '</a>';

            $valid &= $this->validateAndUpdateValue(
                $frontend_key,
                'SHIPMONDO_FRONTEND_KEY',
                $frontend_key_error_html
            );

            $google_error_html =
                '<a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">' .
                $this->trans('Google Maps API key', [], 'Modules.Shipmondo.Admin') .
                '</a>';

            $valid &= $this->validateAndUpdateValue(
                $google_api_key,
                'SHIPMONDO_GOOGLE_API_KEY',
                $google_error_html,
                $frontend_type != 'popup'
            );

            if (!$valid) {
                foreach ($this->validation_errors as $key) {
                    $validation_error_title .= '<li class="test">' . $key . '</li>';
                }
                $output .= $this->displayError($validation_error_title);
            }
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = $this->context->language->id;
        $fields_form = [];

        $prestashop_guide_url = 'https://help.shipmondo.com/articles/7197780';

        // Init fields form
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->trans('Settings', [], 'Modules.Shipmondo.Admin'),
            ],
            'input' => [
                [
                    'name' => 'SHIPMONDO_DESC',
                    'type' => 'html',
                    'html_content' => $this->trans('Follow the setup guide', [], 'Modules.Shipmondo.Admin') . ' :' .
                        ' <a href="' . $prestashop_guide_url . '" target="_blank">' .
                        $this->trans('PrestaShop guide', [], 'Modules.Shipmondo.Admin') .
                        '</a>',
                ],
                [
                    'name' => 'SHIPMONDO_FRONTEND_KEY',
                    'type' => 'text',
                    'label' => $this->trans('Shipping module API key', [], 'Modules.Shipmondo.Admin'),
                    'desc' => $this->trans('Insert your shipping module API key here. You can generate a key from', [], 'Modules.Shipmondo.Admin') . ': 
                        <a target="_blank" href="https://app.shipmondo.com/main/app/#/setting/freight-module">
                            Shipmondo
                        </a>',
                    'required' => true,
                    'col' => 4,
                ],
                [
                    'name' => 'SHIPMONDO_GOOGLE_API_KEY',
                    'type' => 'text',
                    'label' => $this->trans('Google Maps API key', [], 'Modules.Shipmondo.Admin'),
                    'desc' => $this->trans('Insert your Google API key here. You can generate a key from', [], 'Modules.Shipmondo.Admin') . ': 
                        <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">
                            Google
                        </a>',
                    'required' => false,
                    'col' => 4,
                ],
                [
                    'name' => 'SHIPMONDO_FRONTEND_TYPE',
                    'type' => 'radio',
                    'values' => [
                        [
                            'id' => 'option_popup',
                            'value' => 'popup',
                            'label' => $this->trans('Popup', [], 'Modules.Shipmondo.Admin'),
                        ],
                        [
                            'id' => 'option_dropdown',
                            'value' => 'dropdown',
                            'label' => $this->trans('Dropdown', [], 'Modules.Shipmondo.Admin'),
                        ],
                        [
                            'id' => 'option_radio',
                            'value' => 'radio',
                            'label' => $this->trans('Radio button', [], 'Modules.Shipmondo.Admin'),
                        ],
                    ],
                    'label' => $this->trans('Display on checkout', [], 'Modules.Shipmondo.Admin'),
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Modules.Shipmondo.Admin'),
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

    // Required for carrier modules
    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $shipping_cost;
    }

    // Required for carrier modules
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    public function hookDisplayAdminOrderSide($params)
    {
        $servicePoint = $this->get('shipmondo.repository.shipmondo_service_point')
            ->findOneBy(['orderId' => $params['id_order']]);

        if ($servicePoint) {
            return $this->get('twig')->render('@Modules/shipmondo/views/templates/admin/order_side.html.twig', [
                'service_point' => $servicePoint
            ]);
        }
    }

    public function hookDisplayHeader($params)
    {
        $context = $this->context->controller;

        $current_page = Tools::getValue('controller');

        $order_pages = [
            'order', // default PS
        ];

        // Knowband - SuperCheckout
        if (Module::isInstalled('supercheckout') && Module::isEnabled('supercheckout')) {
            $order_pages[] = 'supercheckout';
        }

        // Prestaworks - Easy Checkout (NETS Easy)
        if (Module::isInstalled('easycheckout') && Module::isEnabled('easycheckout')) {
            $order_pages[] = 'checkout';
        }

        if (in_array($current_page, $order_pages)) {
            Media::addJsDef([
                'choose_pickup_point_text' => $this->trans('Choose pickup point'),
                'frontend_type' => Configuration::get('SHIPMONDO_FRONTEND_TYPE'),
                'modal_html' => $this->fetch('module:shipmondo/views/templates/front/popup/modal.tpl'),
                'module_base_url' => Tools::getProtocol(Tools::usingSecureMode()) . $_SERVER['HTTP_HOST'] . $this->getPathUri(),
                'service_points_endpoint' => Context::getContext()->link->getModuleLink('shipmondo', 'servicepoints'),
                'extentions_endpoint' => Context::getContext()->link->getModuleLink('shipmondo', 'extensions'),
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
                'warehouse',
            ];

            if (in_array(_THEME_NAME_, $themes)) {
                $context->addCSS($this->_path . 'views/css/theme/' . _THEME_NAME_ . '.css', 'all');
            }

            // Add module overrides to views/css/module.
            $modules = [
                // Add modules into this array
                'onepagecheckoutps',
                // Prestateam - Tested with v1.0.3
                'supercheckout',
                // Knowband - Tested with v4.0.6,
                'thecheckout',
                // Prestamodules / Zelarg - Tested with v3.2.5
                'easycheckout', // Easycheckout - Tested with v.1.2.11
            ];
            foreach ($modules as $module) {
                if (Module::isInstalled($module) && Module::isEnabled($module)) {
                    // Major changes in 7
                    if ($module == 'supercheckout' && Module::getInstanceByName('supercheckout')->version < 7) {
                        $context->addCSS($this->_path . 'views/css/module/supercheckout_pre7.css', 'all');
                        //$context->addJS($this->_path . 'views/js/module/supercheckout_pre7.js', 'all');
                    } else {
                        $context->addCSS($this->_path . 'views/css/module/' . $module . '.css', 'all');
                        //$context->addJS($this->_path . 'views/js/module/' . $module . '.js', 'all');
                    }
                }
            }

            $context->addJS($this->_path . 'views/js/shipmondo.js', 'all');
        }
    }


    public function hookDisplayCarrierExtraContent($params)
    {
        $carrier = $this->get('shipmondo.repository.shipmondo_carrier')->findOneBy(['carrierId' => $params['carrier']['id_reference']]);

        if ($carrier && $carrier->getProductCode() === 'service_point') {
            $servicePoint = $this->get('shipmondo.repository.shipmondo_service_point')
                ->findOneBy([
                    'cartId' => $params['cart']->id,
                    'carrierCode' => $carrier->getCarrierCode()
                ]);

            $this->context->smarty->assign([
                'carrier_code' => $carrier->getCarrierCode(),
                'carrier_id' => $params['carrier']['id'],
                'service_point' => $servicePoint
            ]);
            return $this->fetch('module:shipmondo/views/templates/front/' . Configuration::get('SHIPMONDO_FRONTEND_TYPE') . '/selection_button.tpl');
        }
    }

    public function hookNewOrder($params)
    {
        $carrier = new Carrier((int) $params['order']->id_carrier);
        $smdCarrier = $this->get('shipmondo.repository.shipmondo_carrier')->findOneBy(['carrierId' => $carrier->id_reference]);

        if ($smdCarrier && $smdCarrier->getProductCode() === 'service_point') {
            $servicePoint = $this->get('shipmondo.repository.shipmondo_service_point')
                ->findOneBy([
                    'cartId' => $params['cart']->id,
                    'carrierCode' => $smdCarrier->getCarrierCode()
                ]);

            if ($servicePoint) {
                $servicePoint->setOrderId($params['order']->id);

                $entityManager = $this->get('doctrine.orm.entity_manager');
                $entityManager->persist($servicePoint);
                $entityManager->flush();
            }
        }
    }

    protected function createDatabaseTables()
    {
        $db_instance = Db::getInstance();
        return $this->createSelectedServicePointsTable($db_instance) &&
            $this->createCarrierTable($db_instance) &&
            $this->createServicePointTable($db_instance);
    }

    protected function createSelectedServicePointsTable($db_instance)
    {
        $sql_carts = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_selected_service_points` ('
            . '`id_smd_service_point` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . '`id_cart` int(10), '
            . '`service_point` text, '
            . '`id_carrier` int(10)'
            . ')';

        return $db_instance->execute($sql_carts);
    }

    protected function createCarrierTable($db_instance)
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_carrier` ('
            . '`id_smd_carrier` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . '`id_carrier` INT NOT NULL, '
            . '`carrier_code` VARCHAR(255) NOT NULL, '
            . '`product_code` VARCHAR(255) NOT NULL'
            . ')';

        return $db_instance->execute($sql);
    }

    protected function createServicePointTable($db_instance)
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_service_point` ('
            . 'id_smd_service_point INT AUTO_INCREMENT NOT NULL PRIMARY KEY, '
            . 'id_cart INT, '
            . 'id_order INT, '
            . 'carrier_code VARCHAR(255) NOT NULL, '
            . 'service_point_id VARCHAR(255) NOT NULL, '
            . 'name VARCHAR(255) NOT NULL, '
            . 'address1 VARCHAR(255) NOT NULL, '
            . 'address2 VARCHAR(255), '
            . 'zip_code VARCHAR(255) NOT NULL, '
            . 'city VARCHAR(255) NOT NULL, '
            . 'country_code VARCHAR(2) NOT NULL'
            . ')';

        return $db_instance->execute($sql);
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
        $success = true;

        $tables = [
            'shipmondo_selected_service_points',
            'shipmondo_carrier',
            'shipmondo_service_point'
        ];

        $db_instance = Db::getInstance();
        foreach ($tables as $table) {
            $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`';

            if (!$db_instance->execute($sql)) {
                $success = false;
            }
        }

        return $success;
    }

    protected function deleteSettings()
    {
        Configuration::deleteByName('SHIPMONDO_FRONTEND_KEY');
        Configuration::deleteByName('SHIPMONDO_GOOGLE_API_KEY');
        Configuration::deleteByName('SHIPMONDO_FRONTEND_TYPE');

        return true;
    }

    private function validateAndUpdateValue($value, $value_key, $error_message, $optional = false)
    {
        if (empty($value) || !Validate::isGenericName($value)) {
            Configuration::updateValue($value_key, '');

            if ($optional) {
                return true;
            } else {
                $this->validation_errors[] = $error_message;
                return false;
            }
        }

        Configuration::updateValue($value_key, $value);

        return true;
    }
}
