<?php

namespace App\Repository;

use App\Entity\AudioRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AudioRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method AudioRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method AudioRequest[]    findAll()
 * @method AudioRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AudioRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AudioRequest::class);
    }

    /**
     * @param \DateInterval $since
     * @return AudioRequest[]
     * @throws \Exception
     */
    public function findSince(\DateInterval $since): iterable
    {
        $qb = $this->createQueryBuilder('ar');

        return $qb
            ->andWhere(
                $qb->expr()->between(
                    'ar.createdAt',
                    ':since',
                    ':from'
                )
            )
            ->setParameters([
                'since' => (new \DateTimeImmutable('now'))->sub($since),
                'from' => new \DateTimeImmutable('now')
            ])
            ->getQuery()
            ->getResult();
    }


}
