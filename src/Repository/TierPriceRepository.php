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

class TierPriceRepository extends EntityRepository implements TierPriceRepositoryInterface
{
    /** {@inheritdoc}
     *
     * @return TierPriceInterface[]
     */
    public function getSortedTierPrices(TierPriceableInterface $productVariant, ChannelInterface $channel): array
    {
        $qb = $this->createQueryBuilder('tp');

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $qb
            ->where('productVariant = :productVariant')
            ->andWhere('channel = :channel')
            ->andWhere($expr->orX(
                'startsAt IS NULL',
                'startsAt <= :now'
            ))
            ->orderBy('qty', 'ASC')
            ->addOrderBy('startsAt', 'DESC')
            ->setParameter('productVariant', $productVariant)
            ->setParameter('channel', $channel)
            ->setParameter('now', new \DateTime())
        ;

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
