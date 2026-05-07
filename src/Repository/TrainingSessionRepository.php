<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\TrainingSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingSession>
 */
class TrainingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingSession::class);
    }

    /**
     * Retourne les entraînements visibles selon l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit tous les entraînements
     *
     * ROLE_ADMIN :
     * - voit les entraînements des équipes de son club
     *
     * ROLE_COACH :
     * - voit les entraînements des équipes qu'il encadre
     *
     * @return TrainingSession[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createQueryBuilder('ts')
            ->leftJoin('ts.team', 't')
            ->addSelect('t')
            ->leftJoin('t.club', 'club')
            ->addSelect('club')
            ->orderBy('ts.trainingDate', 'DESC')
            ->addOrderBy('ts.startTime', 'ASC');

        $roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
            return $queryBuilder
                ->getQuery()
                ->getResult();
        }

        if (in_array('ROLE_ADMIN', $roles, true)) {
            $club = $user->getClub();

            if ($club === null) {
                return [];
            }

            return $queryBuilder
                ->andWhere('t.club = :club')
                ->setParameter('club', $club)
                ->getQuery()
                ->getResult();
        }

        if (in_array('ROLE_COACH', $roles, true)) {
            $coachedTeams = $user->getCoachedTeams()->toArray();

            if (count($coachedTeams) === 0) {
                return [];
            }

            return $queryBuilder
                ->andWhere('ts.team IN (:teams)')
                ->setParameter('teams', $coachedTeams)
                ->getQuery()
                ->getResult();
        }

        return [];
    }
}