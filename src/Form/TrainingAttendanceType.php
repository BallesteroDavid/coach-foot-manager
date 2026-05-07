<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Player;
use App\Entity\TrainingAttendance;
use App\Entity\TrainingSession;
use App\Repository\PlayerRepository;
use App\Repository\TrainingSessionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingAttendanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['hide_training_session']) {
            $builder->add('trainingSession', EntityType::class, [
                'class' => TrainingSession::class,
                'query_builder' => function (TrainingSessionRepository $trainingSessionRepository) use ($options) {
                    $queryBuilder = $trainingSessionRepository->createQueryBuilder('ts')
                        ->leftJoin('ts.team', 'team')
                        ->addSelect('team')
                        ->leftJoin('team.club', 'club')
                        ->addSelect('club')
                        ->orderBy('ts.trainingDate', 'DESC')
                        ->addOrderBy('ts.startTime', 'ASC');

                    $user = $options['current_user'];

                    if (!$user instanceof AppUser) {
                        return $queryBuilder->andWhere('1 = 0');
                    }

                    $roles = $user->getRoles();

                    if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                        return $queryBuilder;
                    }

                    if (in_array('ROLE_ADMIN', $roles, true)) {
                        if ($user->getClub() === null) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('team.club = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    if (in_array('ROLE_COACH', $roles, true)) {
                        $coachedTeams = $user->getCoachedTeams()->toArray();

                        if (count($coachedTeams) === 0) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('ts.team IN (:teams)')
                            ->setParameter('teams', $coachedTeams);
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => function (TrainingSession $trainingSession) {
                    $team = $trainingSession->getTeam()?->getName() ?? 'Équipe inconnue';
                    $date = $trainingSession->getTrainingDate()?->format('d/m/Y') ?? 'Date inconnue';

                    return $date . ' - ' . $team . ' - ' . $trainingSession->getTimeRangeLabel();
                },
                'placeholder' => 'Choisir un entraînement',
                'label' => 'Entraînement',
            ]);
        }

        $builder
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'query_builder' => function (PlayerRepository $playerRepository) use ($options) {
                    $queryBuilder = $playerRepository->createQueryBuilder('p')
                        ->leftJoin('p.team', 'team')
                        ->addSelect('team')
                        ->leftJoin('team.club', 'club')
                        ->addSelect('club')
                        ->orderBy('p.lastName', 'ASC')
                        ->addOrderBy('p.firstName', 'ASC');

                    if (
                        $options['training_session'] instanceof TrainingSession
                        && $options['training_session']->getTeam() !== null
                    ) {
                        return $queryBuilder
                            ->andWhere('p.team = :team')
                            ->setParameter('team', $options['training_session']->getTeam());
                    }

                    $user = $options['current_user'];

                    if (!$user instanceof AppUser) {
                        return $queryBuilder->andWhere('1 = 0');
                    }

                    $roles = $user->getRoles();

                    if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                        return $queryBuilder;
                    }

                    if (in_array('ROLE_ADMIN', $roles, true)) {
                        if ($user->getClub() === null) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('team.club = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    if (in_array('ROLE_COACH', $roles, true)) {
                        $coachedTeams = $user->getCoachedTeams()->toArray();

                        if (count($coachedTeams) === 0) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('p.team IN (:teams)')
                            ->setParameter('teams', $coachedTeams);
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => function (Player $player) {
                    $team = $player->getTeam()?->getName() ?? 'Sans équipe';

                    return $player->getFullName() . ' - ' . $team;
                },
                'placeholder' => 'Choisir un joueur',
                'label' => 'Joueur',
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Présent' => 'present',
                    'Absent' => 'absent',
                    'Excusé' => 'excused',
                    'En retard' => 'late',
                    'Blessé' => 'injured',
                    'Dispensé' => 'exempt',
                ],
            ])

            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TrainingAttendance::class,
            'hide_training_session' => false,
            'training_session' => null,
            'current_user' => null,
        ]);
    }
}