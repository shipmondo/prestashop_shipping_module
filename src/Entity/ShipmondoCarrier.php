<?php

namespace Shipmondo\Entity;

use Doctrine\ORM\Mapping as ORM;
use Configuration;


/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class ShipmondoCarrier
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_smd_carrier", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_carrier", type="integer")
     */
    private $carrierId;

    /**
     * @var string
     *
     * @ORM\Column(name="carrier_code", type="string", length=255)
     */
    private $carrierCode;

    /**
     * @var string
     *
     * @ORM\Column(name="product_code", type="string", length=255)
     */
    private $productCode;

    /**
     * @var string
     */
    private $carrierName;
    
    /**
     * @var string
     */
    private $productName;

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of carrierId
     *
     * @return int
     */
    public function getCarrierId()
    {
        return $this->carrierId;
    }

    /**
     * Set the value of carrierId
     *
     * @param int $carrierId
     * @return self
     */
    public function setCarrierId($carrierId)
    {
        $this->carrierId = $carrierId;
        return $this;
    }

    /**
     * Get the value of carrierCode
     *
     * @return string
     */
    public function getCarrierCode()
    {
        return $this->carrierCode;
    }

    /**
     * Set the value of carrierCode
     *
     * @param string $carrierCode
     * @return self
     */
    public function setCarrierCode($carrierCode)
    {
        $this->carrierCode = $carrierCode;
        return $this;
    }

    /**
     * Get the value of productCode
     *
     * @return string
     */
    public function getProductCode()
    {
        return $this->productCode;
    }

    /**
     * Set the value of productCode
     *
     * @param string $productCode
     * @return self
     */
    public function setProductCode($productCode)
    {
        $this->productCode = $productCode;
        return $this;
    }

    /**
     * Summary of getCarrierName
     * @return string|null
     */
    public function getCarrierName()
    {
        if ($this->carrierName) {
            return $this->carrierName;
        }

        $carriers = self::getAvailableCarriers();
        foreach ($carriers as $carrier) {
            if ($carrier->code === $this->carrierCode) {
                $this->carrierName = $carrier->name;
            }
        }

        return $this->carrierName;
    }

    public function getProductName()
    {
        if ($this->productName) {
            return $this->productName;
        }

        $products = self::getAvailableProducts($this->carrierCode);
        foreach ($products as $product) {
            if ($product->code === $this->productCode) {
                $this->productName = $product->name;
            }
        }

        return $this->productName;
    }

    /**
     * Convert the entity to an array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id_smd_carrier' => $this->getId(),
            'id_carrier' => $this->getCarrierId(),
            'carrier_code' => $this->getCarrierCode(),
            'product_code' => $this->getProductCode()
        ];
    }
}