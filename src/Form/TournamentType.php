<?php

namespace App\Form;

use App\Entity\Tournament;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du tournoi',
                'attr' => ['class' => 'form-control']
            ])
            ->add('game', ChoiceType::class, [
                'label' => 'Jeu',
                'choices' => [
                    'League of Legends' => 'League of Legends',
                    'Counter-Strike 2' => 'CS2',
                    'Valorant' => 'Valorant',
                    'Dota 2' => 'Dota 2',
                    'Rocket League' => 'Rocket League',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('format', ChoiceType::class, [
                'label' => 'Format',
                'choices' => [
                    'Élimination Simple' => 'single_elimination',
                    'Élimination Double' => 'double_elimination',
                    'Ligue' => 'league',
                    'Swiss' => 'swiss',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('maxTeams', IntegerType::class, [
                'label' => 'Nombre maximum d\'équipes',
                'attr' => ['class' => 'form-control']
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('registrationDeadline', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices'  => [
                'Brouillon' => 'draft',
                'Ouvert aux inscriptions' => 'open',
                'En cours' => 'ongoing',
                'Terminé' => 'completed',
                'Annulé' => 'cancelled',
            ],
            'data' => 'draft',  // Default value
            'attr' => ['class' => 'form-control']
            ])
            ->add('prize', TextType::class, [
                'label' => 'Prix / Récompense',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('rules', TextareaType::class, [
                'label' => 'Règles',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 6]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}