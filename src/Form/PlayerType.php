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
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'required' => false,
            ])
            ->add('position', ChoiceType::class, [
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
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'max' => 99,
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Actif' => 'active',
                    'Blessé' => 'injured',
                    'Suspendu' => 'suspended',
                    'Inactif' => 'inactive',
                ],
            ])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une équipe',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Player::class,
        ]);
    }
}