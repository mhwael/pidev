<?php

namespace App\Form;

use App\Entity\MessagesForum;
use App\Entity\SujetsForum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MessagesForumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sujetsForum', EntityType::class, [
                'class' => SujetsForum::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un sujet',
                'required' => false, // ✅ pas de required HTML
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le sujet est obligatoire.']),
                ],
            ])
            ->add('auteur_id', IntegerType::class, [
                'required' => false, // ✅ pas de required HTML
                'constraints' => [
                    new Assert\NotNull(['message' => "L'auteur est obligatoire."]),
                    new Assert\Positive(['message' => "L'auteur doit être un nombre positif."]),
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'required' => false, // ✅ pas de required HTML
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le contenu est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('date_creation', DateTimeType::class, [
                'required' => false,  // ✅ pas de required HTML
                'widget' => 'single_text',
                'html5' => false,     // ✅ évite datetime-local HTML5
                'constraints' => [
                    new Assert\NotNull(['message' => 'La date de création est obligatoire.']),
                ],
            ])
            ->add('nombre_likes', IntegerType::class, [
                'required' => false,
                'empty_data' => '0',
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le nombre de likes doit être >= 0.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessagesForum::class,
        ]);
    }
}
