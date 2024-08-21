<?php

namespace Shipmondo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class ShipmondoServicePoint
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_smd_service_point", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_cart", type="integer")
     */
    private $cartId;

    /**
     * @var int
     *
     * @ORM\Column(name="id_order", type="integer")
     */
    private $orderId;

    /**
     * @var string
     *
     * @ORM\Column(name="carrier_code", type="string", length=255)
     */
    private $carrierCode;

    /**
     * @var string
     *
     * @ORM\Column(name="service_point_id", type="string", length=255)
     */
    private $servicePointId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address1", type="string", length=255)
     */
    private $address1;

    /**
     * @var string
     *
     * @ORM\Column(name="address2", type="string", length=255)
     */
    private $address2;


    /**
     * @var string
     *
     * @ORM\Column(name="zip_code", type="string", length=255)
     */
    private $zipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="country_code", type="string", length=2)
     */
    private $countryCode;

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
     * Get the value of cartId
     *
     * @return int
     */
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * Set the value of cartId
     *
     * @param int $cartId
     * @return self
     */
    public function setCartId($cartId)
    {
        $this->cartId = $cartId;
        return $this;
    }

    /**
     * Get the value of orderId
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set the value of orderId
     *
     * @param int $orderId
     * @return self
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
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
     * Get the value of servicePointId
     *
     * @return string
     */
    public function getServicePointId()
    {
        return $this->servicePointId;
    }

    /**
     * Set the value of servicePointId
     *
     * @param string $servicePointId
     * @return self
     */
    public function setServicePointId($servicePointId)
    {
        $this->servicePointId = $servicePointId;
        return $this;
    }

    /**
     * Get the value of name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the value of address1
     *
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * Set the value of address1
     *
     * @param string $address1
     * @return self
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
        return $this;
    }

    /**
     * Get the value of address2
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set the value of address2
     *
     * @param string $address2
     * @return self
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
        return $this;
    }

    /**
     * Get the value of zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set the value of zipCode
     *
     * @param string $zipCode
     * @return self
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    /**
     * Get the value of city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set the value of city
     *
     * @param string $city
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Get the value of countryCode
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Set the value of countryCode
     *
     * @param string $countryCode
     * @return self
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
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
            'id_smd_service_point' => $this->getId(),
            'id_order' => $this->getOrderId(),
            'carrier_code' => $this->getCarrierCode(),
            'service_point_id' => $this->getServicePointId(),
            'name' => $this->getName(),
            'address1' => $this->getAddress1(),
            'address2' => $this->getAddress2(),
            'zip_code' => $this->getZipCode(),
            'city' => $this->getCity(),
            'country_code' => $this->getCountryCode()
        ];
    }
}