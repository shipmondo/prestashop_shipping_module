<?php

/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 *
 * @ORM\Entity()
 */
class ShipmondoCarrier
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\Column(name="id_smd_carrier", type="integer")
     *
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
     * @var string|null
     *
     * @ORM\Column(name="carrier_product_code", type="string", length=255)
     */
    private $carrierProductCode;

    /**
     * @var array|null
     *
     * @ORM\Column(name="service_point_types", type="simple_array")
     */
    private $servicePointTypes;

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of carrierId
     *
     * @return ?int
     */
    public function getCarrierId(): ?int
    {
        return $this->carrierId;
    }

    /**
     * Set the value of carrierId
     *
     * @param int $carrierId
     *
     * @return self
     */
    public function setCarrierId($carrierId): self
    {
        $this->carrierId = $carrierId;

        return $this;
    }

    /**
     * Get the value of carrierCode
     *
     * @return ?string
     */
    public function getCarrierCode(): ?string
    {
        return $this->carrierCode;
    }

    /**
     * Set the value of carrierCode
     *
     * @param string $carrierCode
     *
     * @return self
     */
    public function setCarrierCode($carrierCode): self
    {
        $this->carrierCode = $carrierCode;

        return $this;
    }

    /**
     * Get the value of productCode
     *
     * @return ?string
     */
    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    /**
     * Set the value of productCode
     *
     * @param string $productCode
     *
     * @return self
     */
    public function setProductCode($productCode): self
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function getCarrierProductCode(): ?string
    {
        return $this->carrierProductCode;
    }

    public function setCarrierProductCode(?string $carrierProductCode): self
    {
        $this->carrierProductCode = $carrierProductCode;

        return $this;
    }

    public function getServicePointTypes()
    {
        $value = $this->servicePointTypes;

        if ($value === null) {
            return [];
        }

        return $value;
    }

    public function setServicePointTypes(?array $servicePointTypes): self
    {
        if (is_array($servicePointTypes)) {
            $uniqueServicePointTypes = [];

            foreach ($servicePointTypes as $servicePointType) {
                if ($servicePointType === null) {
                    continue;
                }

                if (!is_string($servicePointType)) {
                    throw new \InvalidArgumentException('Service point types must be an array of strings');
                }

                if (!in_array($servicePointType, $uniqueServicePointTypes, true)) {
                    array_push($uniqueServicePointTypes, $servicePointType);
                }
            }

            sort($uniqueServicePointTypes);

            $this->servicePointTypes = $uniqueServicePointTypes;
        } else {
            $this->servicePointTypes = $servicePointTypes;
        }

        return $this;
    }

    /**
     * Convert the entity to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id_smd_carrier' => $this->getId(),
            'id_carrier' => $this->getCarrierId(),
            'carrier_code' => $this->getCarrierCode(),
            'product_code' => $this->getProductCode(),
            'carrier_product_code' => $this->getCarrierProductCode(),
            'service_point_types' => $this->getServicePointTypes(),
        ];
    }

    public function setDefaultServicePointFields(): void
    {
        if ($this->getProductCode() === 'service_point') {
            if ($this->getCarrierProductCode() === null) {
                $this->setServicePointTypes(null);
            } elseif ($this->getServicePointTypes() === null) {
                $this->setServicePointTypes([]);
            }

            return;
        }

        $this->setCarrierProductCode(null);
        $this->setServicePointTypes(null);
    }
}
