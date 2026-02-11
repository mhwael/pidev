<?php

namespace App\Form;

use App\Entity\GuideStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // ✨ Needed for file upload
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class GuideStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Step Title',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. Buy Utility']
            ])
            ->add('stepOrder', IntegerType::class, [
                'label' => 'Order (1, 2, 3...)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Instructions',
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            
            // ✨ IMAGE UPLOAD FOR STEP
            ->add('image', FileType::class, [
                'label' => 'Step Image (Optional)',
                'mapped' => false, // Important: We handle this manually in the controller
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'form-control-file mt-2']
            ])

            ->add('videoUrl', TextType::class, [
                'label' => 'YouTube Video URL (Optional)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://youtube.com/...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GuideStep::class,
        ]);
    }
}