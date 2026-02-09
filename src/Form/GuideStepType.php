<?php

namespace App\Form;

use App\Entity\GuideStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // ✨ Import
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType; // ✨ Import
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File; // ✨ Import

class GuideStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['placeholder' => 'Step Title (e.g. Jump here)']
            ])
            
            // ✨ NEW: Step Image Upload
            ->add('image', FileType::class, [
                'label' => 'Step Image (Optional)',
                'mapped' => false, // We handle this manually too
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    ])
                ],
                'attr' => ['class' => 'form-control mb-2'],
            ])

            // ✨ NEW: Video Link
            ->add('videoUrl', UrlType::class, [
                'label' => 'Video Link (YouTube/Clip)',
                'required' => false,
                'attr' => ['placeholder' => 'https://youtube.com/watch?v=...']
            ])

            ->add('content', TextareaType::class, [
                'attr' => ['rows' => 3, 'placeholder' => 'Describe the step...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => GuideStep::class]);
    }
}