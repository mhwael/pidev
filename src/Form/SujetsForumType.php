<?php

namespace App\Form;

use App\Entity\SujetsForum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SujetsForumType extends AbstractType
{
    public const CATEGORIES = [
        'Nouveautés & sorties' => 'Nouveautés & sorties',
        'Tests / Reviews' => 'Tests / Reviews',
        'Top jeux (par genre ou plateforme)' => 'Top jeux (par genre ou plateforme)',
        'Jeux indépendants (Indie Games)' => 'Jeux indépendants (Indie Games)',
        'Jeux gratuits (Free-to-Play)' => 'Jeux gratuits (Free-to-Play)',
        'DLC & extensions' => 'DLC & extensions',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('cree_par', TextType::class)
            ->add('categorie', ChoiceType::class, [
                'choices' => self::CATEGORIES,
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('date_creation', DateTimeType::class)
            ->add('est_verrouille', CheckboxType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SujetsForum::class,
        ]);
    }
}
