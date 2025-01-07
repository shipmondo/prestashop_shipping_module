<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Entity;

use ObjectModel;

/**
 * Entity used to expose Shipmondo Service Points to the Webservice
 */
class ShipmondoServicePointWs extends \ObjectModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $id_cart;

    /**
     * @var int
     */
    public $id_order;

    /**
     * @var string
     */
    public $carrier_code;

    /**
     * @var string
     */
    public $service_point_id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $address1;

    /**
     * @var string
     */
    public $address2;

    /**
     * @var string
     */
    public $zip_code;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $country_code;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'shipmondo_service_point',
        'primary' => 'id_smd_service_point',
        'multilang' => false,
        'fields' => [
            'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'carrier_code' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'service_point_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'address1' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'address2' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'zip_code' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'city' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'country_code' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
        ],
    ];

    protected $webserviceParameters = [
        'objectNodeName' => 'shipmondo_service_point',
        'objectsNodeName' => 'shipmondo_service_points',
        'fields' => [
            'id_cart' => ['xlink_resource' => 'carts'],
            'id_order' => ['xlink_resource' => 'orders'],
            'carrier_code' => ['required' => true],
            'service_point_id' => ['required' => true],
            'name' => ['required' => true],
            'address1' => ['required' => true],
            'address2' => [],
            'zip_code' => ['required' => true],
            'city' => ['required' => true],
            'country_code' => ['required' => true],
        ],
    ];
}
