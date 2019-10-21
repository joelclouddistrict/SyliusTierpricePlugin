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
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Customer\Model\CustomerGroupInterface;

class TierPriceRepository extends EntityRepository implements TierPriceRepositoryInterface
{
    /** {@inheritdoc}
     *
     * @return TierPriceInterface[]
     */
    public function getSortedTierPrices(TierPriceableInterface $productVariant, ChannelInterface $channel,?CustomerGroupInterface $customerGroup = null): array
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $qb = $this->createQueryBuilder('tp')
            ->where('tp.productVariant = :productVariant')
            ->andWhere('tp.channel = :channel')
            ->andWhere($expr->orX(
                'tp.startsAt IS NULL',
                'tp.startsAt <= :now'
            ))
            ->orderBy('tp.qty', 'ASC')
            ->addOrderBy('tp.startsAt', 'DESC')
            ->setParameter('productVariant', $productVariant)
            ->setParameter('channel', $channel)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult()
        ;

        if ($customerGroup instanceof CustomerGroupInterface) {
            $qb
                ->andWhere('tp.customerGroup = :customerGroup')
                ->setParameter('customerGroup', $customerGroup)
            ;
        } else {
            $qb->andWhere('tp.customerGroup IS NULL');
        }

        $result = $qb->getQuery()->getResult();

        // only add first tier found for any quantity
        $qtys = [];
        $prices = [];
        foreach ($result as $tierPrice) {
            if (in_array($tierPrice->getQty(), $qtys)) {
                continue;
            }
            $qtys[] = $tierPrice->getQty();

            $prices[] = $tierPrice;
        }

        return $prices;
    }
}
