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
use Symfony\Component\Validator\Constraints as Assert;

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
            ->add('titre', TextType::class, [
                'required' => false, // ✅ pas de required HTML
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('cree_par', TextType::class, [
                'required' => false, // ✅ pas de required HTML
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le champ "Créé par" est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le champ "Créé par" doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('categorie', ChoiceType::class, [
                'choices' => self::CATEGORIES,
                'placeholder' => 'Choisir une catégorie',
                'required' => false, // ✅ pas de required HTML
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La catégorie est obligatoire.']),
                    new Assert\Choice([
                        'choices' => array_values(self::CATEGORIES),
                        'message' => 'Veuillez choisir une catégorie valide.',
                    ]),
                ],
            ])
            ->add('date_creation', DateTimeType::class, [
                'required' => false,      // ✅ pas de required HTML
                'widget' => 'single_text',
                'html5' => false,         // ✅ évite datetime-local HTML5
                'constraints' => [
                    new Assert\NotNull(['message' => 'La date de création est obligatoire.']),
                ],
            ])
            ->add('est_verrouille', CheckboxType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SujetsForum::class,
        ]);
    }
}
