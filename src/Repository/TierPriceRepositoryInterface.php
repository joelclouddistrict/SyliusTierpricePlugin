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

namespace Brille24\SyliusTierPricePlugin\Repository;

use Brille24\SyliusTierPricePlugin\Entity\TierPriceInterface;
use Brille24\SyliusTierPricePlugin\Traits\TierPriceableInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Customer\Model\CustomerGroupInterface;

interface TierPriceRepositoryInterface extends ObjectRepository
{
    /**
     * Gets all tier prices for a product variant for a channel and optionally customer group with quantity in ascending order
     *
     * @param TierPriceableInterface    $productVariant
     * @param ChannelInterface          $channel
     * @param CustomerGroupInterface|null $customerGroup
     *
     * @return TierPriceInterface[]
     */
    public function getSortedTierPrices(TierPriceableInterface $productVariant, ChannelInterface $channel, ?CustomerGroupInterface $customerGroup = null): array;
}
