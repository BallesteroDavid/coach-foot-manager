<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\TrainingAttendance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingAttendance>
 */
class TrainingAttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingAttendance::class);
    }

    /**
     * Retourne les présences visibles selon l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit toutes les présences
     *
     * ROLE_ADMIN :
     * - voit les présences des entraînements des équipes de son club
     *
     * ROLE_COACH :
     * - voit les présences des entraînements des équipes qu'il encadre
     *
     * @return TrainingAttendance[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createQueryBuilder('ta')
            ->leftJoin('ta.trainingSession', 'ts')
            ->addSelect('ts')
            ->leftJoin('ts.team', 'team')
            ->addSelect('team')
            ->leftJoin('team.club', 'club')
            ->addSelect('club')
            ->leftJoin('ta.player', 'player')
            ->addSelect('player')
            ->orderBy('ta.createdAt', 'DESC');

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
                ->andWhere('team.club = :club')
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