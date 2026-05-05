<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Club;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom de la catégorie',
                'help' => 'Exemple : U9, U11, U13, U15, Seniors.',
            ])

            ->add('ageMin', IntegerType::class, [
                'label' => 'Âge minimum',
                'required' => false,
                'help' => "Optionnel. Âge minimum indicatif pour cette catégorie.",
                'attr' => [
                    'min' => 0,
                ],
            ])

            ->add('ageMax', IntegerType::class, [
                'label' => 'Âge maximum',
                'required' => false,
                'help' => "Optionnel. Âge maximum indicatif pour cette catégorie.",
                'attr' => [
                    'min' => 0,
                ],
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
            'data_class' => Category::class,
        ]);
    }
}