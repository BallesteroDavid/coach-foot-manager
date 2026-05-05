<?php

namespace App\Form;

use App\Entity\Club;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => "Nom de l'équipe",
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'help' => 'Description optionnelle de l’équipe.',
            ])

            ->add('club', EntityType::class, [
                'class' => Club::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un club',
                'label' => 'Club',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
        ]);
    }
}