<?php

namespace Shipmondo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

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