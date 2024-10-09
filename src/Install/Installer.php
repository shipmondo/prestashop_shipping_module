<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Install;

use Configuration;
use Db;
use Module;

class Installer
{
    /**
     * @var string[]
     */
    const HOOKS = [
        'displayAdminOrderSide',
        'displayHeader',
        'displayAfterCarrier',
        'newOrder',
        'actionCarrierUpdate',
        'addWebserviceResources'
    ];

    /**
     * @var string[]
     */
    const TABLES = [
        'shipmondo_carrier',
        'shipmondo_service_point'
    ];

    /**
     * @var Module
     */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function install()
    {
        $dbInstance = Db::getInstance();
        return $this->registerHooks()
            && $this->createCarrierTable($dbInstance)
            && $this->createServicePointTable($dbInstance)
            && $this->setDefaultFrontendType();
    }

    public function uninstall()
    {
        return $this->unregisterHooks()
            && $this->deleteDatabaseTables()
            && $this->deleteSettings();
    }

    public function createCarrierTable(Db $dbInstance)
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipmondo_carrier` ('
            . '`id_smd_carrier` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, '
            . '`id_carrier` INT NOT NULL, '
            . '`carrier_code` VARCHAR(255) NOT NULL, '
            . '`product_code` VARCHAR(255) NOT NULL'
            . ')';

        return $dbInstance->execute($sql);
    }

    public function createServicePointTable(Db $dbInstance)
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

        return $dbInstance->execute($sql);
    }

    private function registerHooks()
    {
        $success = true;

        foreach (self::HOOKS as $hook) {
            if (!$this->module->registerHook($hook)) {
                $success = false;
            }
        }

        return $success;
    }

    private function unregisterHooks()
    {
        $success = true;

        foreach (self::HOOKS as $hook) {
            if (!$this->module->unregisterHook($hook)) {
                $success = false;
            }
        }

        return $success;
    }

    // If frontend type is not set, set as dropdown
    private function setDefaultFrontendType()
    {
        $frontendType = Configuration::get('SHIPMONDO_FRONTEND_TYPE');
        if (empty($frontendType)) {
            Configuration::updateValue('SHIPMONDO_FRONTEND_TYPE', 'dropdown');
        }

        return true;
    }

    private function deleteDatabaseTables()
    {
        $success = true;

        $dbInstance = Db::getInstance();
        foreach (self::TABLES as $table) {
            $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`';

            if (!$dbInstance->execute($sql)) {
                $success = false;
            }
        }

        return $success;
    }

    private function deleteSettings()
    {
        $keys =[
            'SHIPMONDO_FRONTEND_KEY',
            'SHIPMONDO_GOOGLE_API_KEY',
            'SHIPMONDO_FRONTEND_TYPE',
            'SHIPMONDO_AVAILABLE_CARRIERS',
            'SHIPMONDO_AVAILABLE_CARRIERS_EXPIRATION'
        ];

        foreach ($keys as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }
}