<?php

namespace App\Form;

use App\Entity\Player;
use App\Entity\TrainingAttendance;
use App\Entity\TrainingSession;
use App\Repository\PlayerRepository;
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
                        ->orderBy('p.lastName', 'ASC')
                        ->addOrderBy('p.firstName', 'ASC');

                    if (
                        $options['training_session'] instanceof TrainingSession
                        && $options['training_session']->getTeam() !== null
                    ) {
                        $queryBuilder
                            ->andWhere('p.team = :team')
                            ->setParameter('team', $options['training_session']->getTeam());
                    }

                    return $queryBuilder;
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
        ]);
    }
}