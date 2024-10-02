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
            'max' => '8.99.99', // TODO Limit to 8 or use _PS_VERSION_?
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
    public function getOrderShippingCost($params, $shippingCost)
    {
        return $shippingCost;
    }

    // Required for carrier modules
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    // Declares that module uses the new translation system
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function hookDisplayAdminOrderSide($params): string
    {
        $servicePoint = $this->get('shipmondo.repository.shipmondo_service_point')
            ->findOneBy(['orderId' => $params['id_order']]);

        if ($servicePoint) {
            return $this->get('twig')->render('@Modules/shipmondo/views/templates/admin/order_side.html.twig', [
                'service_point' => $servicePoint,
                'carrier_name' => $this->get('shipmondo.carrier_handler')->getCarrierName($servicePoint->getCarrierCode())
            ]);
        }

        return '';
    }

    public function hookDisplayHeader($params): void
    {
        $context = $this->context->controller;

        $currentPage = Tools::getValue('controller');

        $orderPages = [
            'order', // default PS
        ];

        // Knowband - SuperCheckout
        if (Module::isInstalled('supercheckout') && Module::isEnabled('supercheckout')) {
            $orderPages[] = 'supercheckout';
        }

        // Prestaworks - Easy Checkout (NETS Easy)
        if (Module::isInstalled('easycheckout') && Module::isEnabled('easycheckout')) {
            $orderPages[] = 'checkout';
        }

        if (in_array($currentPage, $orderPages)) {
            $servicePointCarriers = $this->get('shipmondo.repository.shipmondo_carrier')->findBy(['productCode' => 'service_point']);
            $servicePointCarrierIds = array_map(function ($servicePointCarrier) {
                return $servicePointCarrier->getCarrierId();
            }, $servicePointCarriers);

            Media::addJsDef([
                'shipmondoModule' => [
                    'deliveryOptionSelector' => '.delivery-option input',
                    'frontendType' => Configuration::get('SHIPMONDO_FRONTEND_TYPE'),
                    'modulePath' => $this->getPathUri(),
                    'servicePointsEndpoint' => Context::getContext()->link->getModuleLink('shipmondo', 'servicepoints'),
                    'servicePointCarrierIds' => $servicePointCarrierIds,
                    'googleMapsApiKey' => Configuration::get('SHIPMONDO_GOOGLE_API_KEY'),
                ]
            ]);

            $context->addCSS($this->getPathUri() . 'views/css/shipmondo.css', 'all');

            // Add module overrides to views/css/module.
            $modules = [
                'supercheckout',
            ];
            foreach ($modules as $module) {
                if (Module::isInstalled($module) && Module::isEnabled($module)) {
                    $cssPath = $this->getPathUri() . 'views/css/module/' . $module . '.css';
                    if (file_exists($cssPath)) {
                        $context->addCSS($cssPath, 'all');
                    }

                    $jsPath = $this->getPathUri() . 'views/js/module/' . $module . '.js';
                    if (file_exists($jsPath)) {
                        $context->addJS($jsPath, 'all');
                    }
                }
            }

            $context->addJS($this->getPathUri() . 'views/js/shipmondo.js', 'all');
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
        $frontendType = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
        if (empty($frontendType)) {
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
