<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
            ])
            ->add('price', MoneyType::class, [
                'required' => true,
                'currency' => false,
            ])
            ->add('stock', IntegerType::class, [
                'required' => true,
            ])
            ->add('image', UrlType::class, [
                'required' => true,
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
                    'Merch' => 'Merch',
                ],
            ]);

        // IMPORTANT: no ->add('orders')
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
