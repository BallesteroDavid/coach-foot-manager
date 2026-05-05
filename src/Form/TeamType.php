<?php

namespace App\Form;

use App\Entity\Club;
use App\Entity\Team;
use App\Entity\Category;
use App\Entity\Season;
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

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
                'required' => false,
                'label' => 'Catégorie',
                'help' => 'Optionnel : permet de classer l’équipe par catégorie d’âge.',
            ])

            ->add('season', EntityType::class, [
                'class' => Season::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une saison',
                'required' => false,
                'label' => 'Saison',
                'help' => 'Optionnel pour l’instant : permet de rattacher l’équipe à une saison sportive.',
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