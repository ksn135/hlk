<?php

namespace App\Repository;

use App\Entity\ReviewPackage;
use App\Enum\ReviewPackageStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReviewPackage>
 */
class ReviewPackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewPackage::class);
    }

    /** @return list<ReviewPackage> */
    public function findActiveForContractor(int $contractorId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.contractorId = :cid')
            ->andWhere('p.status = :status')
            ->setParameter('cid', $contractorId)
            ->setParameter('status', ReviewPackageStatus::Active)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<ReviewPackage> */
    public function findArchiveForContractor(int $contractorId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.contractorId = :cid')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('cid', $contractorId)
            ->setParameter('statuses', [ReviewPackageStatus::Submitted, ReviewPackageStatus::Revoked])
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByGuidForContractor(string $guid, int $contractorId): ?ReviewPackage
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.guid = :guid')
            ->andWhere('p.contractorId = :cid')
            ->setParameter('guid', $guid)
            ->setParameter('cid', $contractorId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
