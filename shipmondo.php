<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Shipmondo\Controller\Admin\ShipmondoCarrierController;
use Shipmondo\Install\Installer;

class Shipmondo extends CarrierModule
{
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
        $this->displayName = $this->trans('Shipmondo Delivery Checkout', [], 'Modules.Shipmondo.Admin');
        $this->description = $this->trans('A complete shipping solution for PrestaShop', [], 'Modules.Shipmondo.Admin');

        $this->tabs = [
            [
                'name' => $this->trans('Shipmondo carriers', [], 'Modules.Shipmondo.Admin'),
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
            $installer = new Installer($this);

            return $installer->install();
        }

        return false;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            $installer = new Installer($this);

            return $installer->uninstall();
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
                'servicePoint' => $servicePoint,
                'countryName' => Country::getNameById($this->context->language->id, Country::getByIso($servicePoint->getCountryCode())),
            ]);
        }

        return '';
    }

    public function hookDisplayHeader($params): void
    {
        $controller = $this->context->controller;

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
                    'servicePointsEndpoint' => $this->context->link->getModuleLink('shipmondo', 'servicepoints'),
                    'servicePointCarrierIds' => $servicePointCarrierIds,
                    'googleMapsApiKey' => Configuration::get('SHIPMONDO_GOOGLE_API_KEY'),
                ],
            ]);

            $controller->addCSS($this->getPathUri() . 'views/css/shipmondo.css', 'all');

            // Add module overrides to views/css/module.
            $modules = [
                'supercheckout',
            ];
            foreach ($modules as $module) {
                if (Module::isInstalled($module) && Module::isEnabled($module)) {
                    $cssPath = 'views/css/module/' . $module . '.css';
                    if (file_exists($this->getLocalPath() . $cssPath)) {
                        $controller->addCSS($this->getPathUri() . $cssPath, 'all');
                    }

                    $jsPath = 'views/js/module/' . $module . '.js';
                    if (file_exists($this->getLocalPath() . $jsPath)) {
                        $controller->addJS($this->getPathUri() . $jsPath, 'all');
                    }
                }
            }

            $controller->addJS($this->getPathUri() . 'views/js/shipmondo.js', 'all');
        }
    }

    public function hookDisplayAfterCarrier($params)
    {
        $this->smarty->assign('frontendType', Configuration::get('SHIPMONDO_FRONTEND_TYPE'));

        return $this->fetch('module:shipmondo/views/templates/front/service_points_container.tpl');
    }

    public function hookActionValidateOrder($params)
    {
        $carrier = new Carrier((int) $params['order']->id_carrier);
        $smdCarrier = $this->get('shipmondo.repository.shipmondo_carrier')->findOneBy(['carrierId' => $carrier->id]);

        if ($smdCarrier && $smdCarrier->getProductCode() === 'service_point') {
            $servicePoint = $this->get('shipmondo.repository.shipmondo_service_point')
                ->findOneBy([
                    'cartId' => $params['cart']->id,
                    'carrierCode' => $smdCarrier->getCarrierCode(),
                ]);

            if ($servicePoint) {
                $servicePoint->setOrderId((int) $params['order']->id);

                $entityManager = $this->get('doctrine.orm.entity_manager');
                $entityManager->persist($servicePoint);
                $entityManager->flush();
            }
        }
    }

    public function hookActionCarrierUpdate($params)
    {
        $oldCarrierId = (int) $params['id_carrier'];
        $newCarrierId = (int) $params['carrier']->id;

        $smdCarriers = $this->get('shipmondo.repository.shipmondo_carrier')->findBy(['carrierId' => $oldCarrierId]);
        if ($smdCarriers) {
            foreach ($smdCarriers as $smdCarrier) {
                $smdCarrier->setCarrierId($newCarrierId);
            }

            $this->get('doctrine.orm.entity_manager')->flush();
        }
    }

    public function hookAddWebserviceResources($params)
    {
        return [
            'shipmondo_service_points' => [
                'description' => 'Service point from Shipmondo, that is selected in checkout and order.',
                'class' => '\Shipmondo\Entity\ShipmondoServicePointWs',
                'forbidden_method' => ['PUT', 'POST', 'PATCH', 'DELETE'], // Only GET is allowed
            ],
        ];
    }
}
