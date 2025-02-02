<?php

/**
 * This file is part of the Brille24 tierprice plugin.
 *
 * (c) Brille24 GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Brille24\SyliusTierPricePlugin\Entity;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Customer\Model\CustomerGroupInterface;

/**
 * Class ProductVariant
 *
 * Entity that stores a possible tier pricing for a product
 */
interface TierPriceInterface
{
    /**
     * @return int
     */
    public function getPrice(): int;

    /**
     * @param int $price
     */
    public function setPrice(int $price): void;

    /**
     * @return int
     */
    public function getQty(): int;

    /**
     * This function does not allow negative values
     *
     * @param int $qty
     */
    public function setQty(int $qty): void;

    /**
     * @return ProductVariantInterface
     */
    public function getProductVariant(): ProductVariantInterface;

    /**
     * @param ProductVariantInterface $productVariant
     */
    public function setProductVariant(ProductVariantInterface $productVariant): void;

    /**
     * @return mixed
     */
    public function getId();

    /**
     * @return ChannelInterface|null
     */
    public function getChannel(): ?ChannelInterface;

    /**
     * @param ChannelInterface|null $channel
     */
    public function setChannel(?ChannelInterface $channel): void;

    /**
     * @return \DateTime|null
     */
    public function getStartsAt(): ?\DateTime;

    /**
     * @param \DateTime|null $startsAt
     */
    public function setStartsAt(?\DateTime $startsAt): void;

    /**
     * @return CustomerGroupInterface|null
     */
    public function getCustomerGroup(): ?CustomerGroupInterface;

    /**
     * @param CustomerGroupInterface|null $customerGroup
     */
    public function setCustomerGroup(?CustomerGroupInterface $customerGroup): void;
}
