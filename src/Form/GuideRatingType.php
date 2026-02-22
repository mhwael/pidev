<?php

namespace App\Form;

use App\Entity\Guide;
use App\Entity\GuideRating;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GuideRatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1. Select the Guide
            ->add('guide', EntityType::class, [
                'class' => Guide::class,
                'choice_label' => 'title',
                'placeholder' => 'Select a Guide...',
                'label' => 'Guide',
                'attr' => ['class' => 'form-control mb-3']
            ])

            // 2. Rating Value (Matches your entity property 'ratingValue')
            ->add('ratingValue', ChoiceType::class, [
                'label' => 'Rating',
                'choices'  => [
                    '⭐⭐⭐⭐⭐ (Masterpiece)' => 5,
                    '⭐⭐⭐⭐ (Great)' => 4,
                    '⭐⭐⭐ (Good)' => 3,
                    '⭐⭐ (Okay)' => 2,
                    '⭐ (Bad)' => 1,
                ],
                'attr' => ['class' => 'form-control mb-3']
            ])

            // 3. Comment (Nullable text)
            ->add('comment', TextareaType::class, [
                'label' => 'Your Review',
                'required' => false,
                'attr' => [
                    'class' => 'form-control mb-3',
                    'rows' => 4,
                    'placeholder' => 'Write your thoughts here...'
                ]
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