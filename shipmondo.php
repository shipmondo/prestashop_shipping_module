<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Shipmondo\Controller\Admin\ShipmondoCarrierController;
use Shipmondo\Entity\ShipmondoServicePoint;

class Shipmondo extends CarrierModule
{
    const PREFIX = 'shipmondo_';

    protected $hooks = [
        'displayAdminOrderSide',
        'displayHeader',
        'displayAfterCarrier',
        'newOrder',
        'updateCarrier',
        'addWebserviceResources'
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
            'min' => '8.0.0',
            'max' => '8.99.99',
        ];
        $this->displayName = "Shipmondo";
        $this->description = $this->trans('A complete shipping solution for PrestaShop', [], 'Modules.Shipmondo.Admin');

        $this->tabs = [
            [
                'name' => 'Shipmondo',
                'class_name' => ShipmondoCarrierController::TAB_CLASS_NAME,
                'route_name' => 'shipmondo_shipmondo_carriers_search',
                'visible' => true,
                'parent_class_name' => 'AdminParentShipping',
            ],
        ];
    }

    public function getContent()
    {
        $route = $this->get('router')->generate('shipmondo_configuration');
        Tools::redirectAdmin($route);
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

    // Declares that module uses the new translation system
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function hookDisplayAdminOrderSide($params)
    {
        $servicePoint = $this->get('shipmondo.repository.shipmondo_service_point')
            ->findOneBy(['orderId' => $params['id_order']]);

        if ($servicePoint) {
            return $this->get('twig')->render('@Modules/shipmondo/views/templates/admin/order_side.html.twig', [
                'service_point' => $servicePoint,
                'carrier_name' => $this->get('shipmondo.carrier_handler')->getCarrierName($servicePoint->getCarrierCode())
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
            $servicePointCarriers = $this->get('shipmondo.repository.shipmondo_carrier')->findBy(['productCode' => 'service_point']);
            $servicePointCarrierIds = array_map(function ($servicePointCarrier) {
                return $servicePointCarrier->getCarrierId();
            }, $servicePointCarriers);

            Media::addJsDef([
                'shipmondo_shipping_module' => [
                    //'choose_pickup_point_text' => $this->trans('Choose pickup point', [], 'Modules.Shipmondo.Front'),
                    'delivery_option_selector' => '.delivery-option input', //testing
                    'frontend_type' => Configuration::get('SHIPMONDO_FRONTEND_TYPE'),
                    //'modal_html' => $this->fetch('module:shipmondo/views/templates/front/popup/modal.tpl'), //TODO Jan?
                    'module_base_url' => Tools::getProtocol(Tools::usingSecureMode()) . $_SERVER['HTTP_HOST'] . $this->getPathUri(),
                    'service_points_endpoint' => Context::getContext()->link->getModuleLink('shipmondo', 'servicepoints'),
                    'extentions_endpoint' => Context::getContext()->link->getModuleLink('shipmondo', 'extensions'),
                    'service_point_carrier_ids' => $servicePointCarrierIds,
                ]
            ]);

            if (Configuration::get('SHIPMONDO_FRONTEND_TYPE') === 'popup') {
                // Loads Google map API
                $context->registerJavascript(
                    'google-maps',
                    'https://maps.googleapis.com/maps/api/js?loading=async&callback=googleMapsInit&key=' . Configuration::get('SHIPMONDO_GOOGLE_API_KEY'),
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
                //'warehouse',
            ];

            if (in_array(_THEME_NAME_, $themes)) {
                $context->addCSS($this->_path . 'views/css/theme/' . _THEME_NAME_ . '.css', 'all');
            }

            // Add module overrides to views/css/module.
            $modules = [
                // Add modules into this array
                //'onepagecheckoutps',
                // Prestateam - Tested with v1.0.3
                'supercheckout',
                // Knowband - Tested with v4.0.6,
                //'thecheckout',
                // Prestamodules / Zelarg - Tested with v3.2.5
                //'easycheckout', // Easycheckout - Tested with v.1.2.11
            ];
            foreach ($modules as $module) {
                if (Module::isInstalled($module) && Module::isEnabled($module)) {
                    // Major changes in 7
                  /* No more support for that if ($module == 'supercheckout' && Module::getInstanceByName('supercheckout')->version < 7) {
                        $context->addCSS($this->_path . 'views/css/module/supercheckout_pre7.css', 'all');
                        $context->addJS($this->_path . 'views/js/module/supercheckout_pre7.js', 'all');
                    } else {
                    */
                        $context->addCSS($this->_path . 'views/css/module/' . $module . '.css', 'all');
                        $context->addJS($this->_path . 'views/js/module/' . $module . '.js', 'all');
                   // }
                }
            }

            $context->addJS($this->_path . 'views/js/shipmondo.js', 'all');
        }
    }


    public function hookDisplayAfterCarrier($params)
    {
        $this->smarty->assign('frontendType', Configuration::get('SHIPMONDO_FRONTEND_TYPE'));

        return $this->fetch('module:shipmondo/views/templates/front/service_points_container.tpl');
    }

    public function hookNewOrder($params)
    {
        $carrier = new Carrier((int) $params['order']->id_carrier);
        $smdCarrier = $this->get('shipmondo.repository.shipmondo_carrier')->findOneBy(['carrierId' => $carrier->id]);

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

    public function hookUpdateCarrier($params)
    {
        $oldCarrierId = (int) $params['id_carrier'];
        $newCarrierId = (int) $params['carrier']->id;

        $smdCarriers = $this->get('shipmondo.repository.shipmondo_carrier')->findBy(['carrierId' => $oldCarrierId]);
        if ($smdCarriers) {
            foreach ($smdCarriers as $smdCarrier) {
                $smdCarrier->setCarrierId($newCarrierId);
            }

            $entityManager = $this->get('doctrine.orm.entity_manager');
            $entityManager->flush();
        }
    }

    public function hookAddWebserviceResources($params)
    {
        return [
            'shipmondo_service_points' => [
                'description' => 'Service point from Shipmondo, that is selected in checkout and order.',
                'class' => '\Shipmondo\Entity\ShipmondoServicePointWs',
                'forbidden_method' => ['PUT', 'POST', 'PATCH', 'DELETE']
            ]
        ];
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
}
