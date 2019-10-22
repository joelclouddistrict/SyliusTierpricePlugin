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

namespace Brille24\SyliusTierPricePlugin\Traits;

use Brille24\SyliusTierPricePlugin\Entity\ProductVariant;
use Brille24\SyliusTierPricePlugin\Entity\TierPriceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Customer\Model\CustomerGroupInterface;

/**
 * Trait TierPriceableTrait
 *
 * Trait that implements the tierpricing functionality.
 * Used in:
 * <li>@see ProductVariant</li>
 */
trait TierPriceableTrait
{
    public function initTierPriceableTrait(): void
    {
        $this->tierPrices = new ArrayCollection();
    }

    /** @var TierPriceInterface[]|ArrayCollection */
    protected $tierPrices;

    /**
     * Returns all tier prices for this product variant.
     *
     * @return TierPriceInterface[]
     */
    public function getTierPrices(): array
    {
        return $this->tierPrices->toArray();
    }

    /**
     * Returns the tier prices only for one channel
     *
     * @param ChannelInterface       $channel
     * @param CustomerInterface|null $customer
     *
     * @return TierPriceInterface[]
     */
    public function getTierPricesForChannel(ChannelInterface $channel, ?CustomerInterface $customer = null): array
    {
        $channelPrices = array_filter($this->getTierPrices(), function (TierPriceInterface $tierPrice) use ($channel) {
            $tierPriceChannel = $tierPrice->getChannel();

            $now = new \DateTime();
            return (
                $tierPriceChannel !== null &&
                $tierPriceChannel->getCode() === $channel->getCode() &&
                ($tierPrice->getStartsAt() === null || $tierPrice->getStartsAt() <= $now)
            );
        });

        return $this->filterPricesWithCustomerGroup($channelPrices, $customer);
    }

    /**
     * Returns the tier prices only for one channel
     *
     * @param string                 $code
     * @param CustomerInterface|null $customer
     *
     * @return TierPriceInterface[]
     */
    public function getTierPricesForChannelCode(string $code, ?CustomerInterface $customer = null): array
    {
        $channelPrices = array_filter($this->getTierPrices(), function (TierPriceInterface $tierPrice) use ($code) {
            $tierPriceChannel = $tierPrice->getChannel();

            $now = new \DateTime();
            return (
                $tierPriceChannel !== null &&
                $tierPriceChannel->getCode() === $code &&
                ($tierPrice->getStartsAt() === null || $tierPrice->getStartsAt() <= $now)
            );
        });

        return $this->filterPricesWithCustomerGroup($channelPrices, $customer);
    }

    /**
     * Removes a tier price from the array collection
     *
     * @param TierPriceInterface $tierPrice
     */
    public function removeTierPrice(TierPriceInterface $tierPrice): void
    {
        $this->tierPrices->removeElement($tierPrice);
    }

    /**
     * Adds an element to the list
     *
     * @param TierPriceInterface $tierPrice
     */
    public function addTierPrice(TierPriceInterface $tierPrice): void
    {
        $tierPrice->setProductVariant($this);
        $this->tierPrices->add($tierPrice);
    }

    /**
     * Sets the tier prices form the array collection
     *
     * @param array $tierPrices
     */
    public function setTierPrices(array $tierPrices): void
    {
        if (!$this instanceof ProductVariantInterface) {
            return;
        }

        $this->tierPrices = new ArrayCollection();

        foreach ($tierPrices as $tierPrice) {
            /** @var TierPriceInterface $tierPrice */
            $this->addTierPrice($tierPrice);
        }
    }

    /**
     * @param array                  $tierPrices
     * @param CustomerInterface|null $customer
     *
     * @return TierPriceInterface[]
     */
    private function filterPricesWithCustomerGroup(array $tierPrices, ?CustomerInterface $customer = null): array
    {
        /*
         * Return the preferred price for qty and customer group tier
         * Prices with a closer startsAt date have precedence
         */
        $tierPrices = $this->filterPricesWithStartsAt($tierPrices);

        $group = null;
        if ($customer instanceof CustomerInterface) {
            $group = $customer->getGroup();
        }

        // CustomerGroup filter not set, return all prices without CustomerGroup
        if (!$group instanceof CustomerGroupInterface) {
            return array_values(array_filter($tierPrices, static function (TierPriceInterface $tierPrice) {
                return $tierPrice->getCustomerGroup() === null;
            }));
        }

        /*
         * Store a preferred price for quantity tier
         * Prices with the selected customer's group have precedence
         */
        $preferredPrices = [];
        foreach ($tierPrices as $tierPrice) {
            // Price for a different CustomerGroup, skip it
            if (
                $tierPrice->getCustomerGroup() instanceof CustomerGroupInterface &&
                $tierPrice->getCustomerGroup()->getCode() !== $group->getCode()
            ) {
                continue;
            }

            $qty = $tierPrice->getQty();
            if (!isset($preferredPrices[$qty])) {
                // Price for quantity not set, store the first one found
                $preferredPrices[$qty] = $tierPrice;
                continue;
            }

            // Price already set, but replace it if this one has the selected customer's group
            if (
                $tierPrice->getCustomerGroup() instanceof CustomerGroupInterface &&
                $tierPrice->getCustomerGroup()->getCode() === $group->getCode()
            ) {
                $preferredPrices[$qty] = $tierPrice;
            }
        }

        return array_values($preferredPrices);
    }

    /**
     * @param array $tierPrices
     *
     * @return TierPriceInterface[]
     */
    private function filterPricesWithStartsAt(array $tierPrices): array
    {
        $now = new \DateTime();
        /*
         * Store a preferred price for quantity tier and customer group
         * Prices with startsAt date closer to $now have precedence
         */
        $preferredPrices = [];
        foreach ($tierPrices as $tierPrice) {
            // Price not available yet, skip it
            if ($tierPrice->getStartsAt() !== null && $tierPrice->getStartsAt() > $now) {
                continue;
            }

            $qty = $tierPrice->getQty();
            $customerGroup = 0;
            if ($tierPrice->getCustomerGroup() instanceof CustomerGroupInterface) {
                $customerGroup = $tierPrice->getCustomerGroup()->getCode();
            }


            if (!isset($preferredPrices[$qty][$customerGroup])) {
                // Price for quantity and customer group not set, store the first one found
                $preferredPrices[$qty][$customerGroup] = $tierPrice;
                continue;
            }

            if ($tierPrice->getStartsAt() === null) {
                continue;
            }

            // Price already set, but replace it if this one has startsAt and the stored one not
            if ($preferredPrices[$qty][$customerGroup]->getStartsAt() === null) {
                $preferredPrices[$qty][$customerGroup] = $tierPrice;
                continue;
            }

            // Price already set, but replace it if this one has startsAt closer to $now
            if ($preferredPrices[$qty][$customerGroup]->getStartsAt() < $tierPrice->getStartsAt()) {
                $preferredPrices[$qty][$customerGroup] = $tierPrice;
            }
        }

        $result = [];
        foreach ($preferredPrices as $preferredPricesForQty) {
            foreach ($preferredPricesForQty as $preferredPriceForCustomerGroup) {
                $result[] = $preferredPriceForCustomerGroup;
            }
        }

        return $result;
    }
}
