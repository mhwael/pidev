<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Guide;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class GuideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Guide Title', 'attr' => ['class' => 'form-control']])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['class' => 'form-control', 'rows' => 4]])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Difficulty',
                'choices'  => ['Easy' => 'Easy', 'Medium' => 'Medium', 'Hard' => 'Hard'],
                'attr' => ['class' => 'form-control']
            ])
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
                'label' => 'Game',
                'attr' => ['class' => 'form-control']
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'required' => false,
                'label' => 'Author',
                'attr' => ['class' => 'form-control']
            ])
            
            // ✅ UPLOAD FIELD (Not in DB directly)
            ->add('coverImage', FileType::class, [
                'label' => 'Cover Image (JPG/PNG)',
                'mapped' => false, 
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'form-control-file'] 
            ])
            
            // ✅ STEPS COLLECTION
            ->add('guideSteps', CollectionType::class, [
                'entry_type' => GuideStepType::class, // Make sure you have this form created!
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Guide::class]);
    }
}