<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Guide;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // ✨ Import this
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File; // ✨ Import this

class GuideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Guide Title'])
            ->add('description', TextareaType::class, ['attr' => ['rows' => 4]])
            ->add('difficulty', ChoiceType::class, [
                'choices'  => ['Easy' => 'Easy', 'Medium' => 'Medium', 'Hard' => 'Hard'],
            ])
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'required' => false,
            ])
            
            // ✨ NEW: Cover Image Upload
            ->add('coverImage', FileType::class, [
                'label' => 'Guide Cover Image (JPG/PNG)',
                'mapped' => false, // Important: We handle the file manually
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'form-control'],
            ])

            ->add('guideSteps', CollectionType::class, [
                'entry_type' => GuideStepType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Guide::class]);
    }
}