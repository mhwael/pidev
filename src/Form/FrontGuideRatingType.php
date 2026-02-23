<?php

namespace App\Form;

use App\Entity\GuideRating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType; // ✨ Required for the button
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontGuideRatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ❌ NO GUIDE FIELD HERE (The Controller handles it automatically)

            // 1. Stars
            ->add('ratingValue', ChoiceType::class, [
                'label' => false,
                'choices'  => [
                    '⭐⭐⭐⭐⭐ Excellent' => 5,
                    '⭐⭐⭐⭐ Very Good' => 4,
                    '⭐⭐⭐ Good' => 3,
                    '⭐⭐ Fair' => 2,
                    '⭐ Poor' => 1,
                ],
                'attr' => ['class' => 'form-control mb-3', 'style' => 'font-size: 0.9rem;']
            ])

            // 2. Comment
            ->add('comment', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control mb-3',
                    'rows' => 3,
                    'placeholder' => 'Write your review...',
                    'style' => 'font-size: 0.9rem;'
                ]
            ])

            // 3. Submit Button (Fixes your Twig Error)
            ->add('submit', SubmitType::class, [
                'label' => 'Post Review',
                'attr' => ['class' => 'btn btn-default w-100', 'style' => 'background: white; color: black; border: 1px solid #ccc; font-weight: bold;']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GuideRating::class,
        ]);
    }
}