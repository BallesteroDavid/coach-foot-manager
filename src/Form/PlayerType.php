<?php

namespace App\Form;

use App\Entity\Player;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $availableTeams = $options['available_teams'];

        $builder
            ->add('firstName', null, [
                'label' => 'Prénom',
            ])

            ->add('lastName', null, [
                'label' => 'Nom',
            ])

            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'label' => 'Date de naissance',
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email du joueur',
                'required' => false,
                'help' => 'Si l’email ou le téléphone du joueur est manquant, les coordonnées du responsable légal seront demandées.',
            ])

            ->add('phone', TelType::class, [
                'label' => 'Téléphone du joueur',
                'required' => false,
                'help' => 'Si l’email ou le téléphone du joueur est manquant, les coordonnées du responsable légal seront demandées.',
            ])

            ->add('guardianEmail', EmailType::class, [
                'label' => 'Email du parent / responsable légal',
                'required' => false,
            ])

            ->add('guardianPhone', TelType::class, [
                'label' => 'Téléphone du parent / responsable légal',
                'required' => false,
            ])

            ->add('position', ChoiceType::class, [
                'label' => 'Poste',
                'required' => false,
                'placeholder' => 'Choisir un poste',
                'choices' => [
                    'Gardien' => 'gardien',
                    'Défenseur' => 'defenseur',
                    'Milieu' => 'milieu',
                    'Attaquant' => 'attaquant',
                ],
            ])

            ->add('jerseyNumber', IntegerType::class, [
                'label' => 'Numéro de maillot',
                'required' => false,
                'help' => 'Le numéro doit être compris entre 1 et 99.',
                'attr' => [
                    'min' => 1,
                    'max' => 99,
                ],
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'active',
                    'Blessé' => 'injured',
                    'Suspendu' => 'suspended',
                    'Inactif' => 'inactive',
                ],
            ])

            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choices' => $availableTeams,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une équipe',
                'required' => false,
                'label' => 'Équipe',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Player::class,
            'available_teams' => [],
        ]);

        $resolver->setAllowedTypes('available_teams', 'array');
    }
}