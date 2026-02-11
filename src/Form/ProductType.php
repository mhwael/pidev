<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('description', TextareaType::class, ['required' => true])
            ->add('price', MoneyType::class, ['required' => true, 'currency' => false])
            ->add('stock', IntegerType::class, ['required' => true])

            // URL (optional)
            ->add('image', UrlType::class, [
                'required' => false,
                'label' => 'Image URL (optional)',
                'attr' => ['placeholder' => 'https://...'],
            ])

            // Upload (optional)
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Upload Image (optional)',
            ])

            ->add('category', ChoiceType::class, [
                'required' => true,
                'placeholder' => 'Choose a category',
                'choices' => [
                    'Games' => 'Games',
                    'Accessories' => 'Accessories',
                    'Consoles' => 'Consoles',
                    'Controllers' => 'Controllers',
                    'Headsets' => 'Headsets',
                    'Gift Cards' => 'Gift Cards',
                ],
            ]);

        // ✅ Custom control de saisie: require URL OR upload
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Product $product */
            $product = $event->getData();
            $form = $event->getForm();

            $url = trim((string) $product?->getImage());
            $file = $form->get('imageFile')->getData();

            if ($url === '' && !$file) {
                $msg = 'You must either paste an image URL or upload an image.';
                // show error under both fields (clear for user)
                $form->get('image')->addError(new FormError($msg));
                $form->get('imageFile')->addError(new FormError($msg));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
