<?php

namespace App\Form;

use App\Entity\Team;
use App\Entity\TrainingSession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingSessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['hide_team']) {
            $builder->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => function (Team $team) {
                    $category = $team->getCategory()?->getName() ?? 'Sans catégorie';
                    $season = $team->getSeason()?->getName() ?? 'Sans saison';

                    return $team->getName() . ' - ' . $category . ' - ' . $season;
                },
                'placeholder' => 'Choisir une équipe',
                'label' => 'Équipe',
            ]);
        }

        $builder
            ->add('trainingDate', DateType::class, [
                'widget' => 'single_text',
                'label' => "Date de l'entraînement",
            ])

            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de début',
            ])

            ->add('endTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de fin',
            ])

            ->add('location', TextType::class, [
                'label' => 'Lieu',
            ])

            ->add('theme', TextType::class, [
                'label' => 'Thème',
                'required' => false,
                'help' => 'Exemple : passes, finition, tactique, physique...',
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
            'data_class' => TrainingSession::class,
            'hide_team' => false,
        ]);
    }
}