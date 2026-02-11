<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'required' => true,
            ])
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'NEW' => 'NEW',
                    'PAID' => 'PAID',
                    'CANCELLED' => 'CANCELLED',
                    'DELIVERED' => 'DELIVERED',
                ],
            ])
            ->add('totalAmount', MoneyType::class, [
                'required' => true,
                'currency' => false,
            ])
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'required' => true,
            ])
            ->add('customerFirstName', TextType::class, [
                'required' => true,
            ])
            ->add('customerLastName', TextType::class, [
                'required' => true,
            ])
            ->add('customerPhone', TelType::class, [
                'required' => true,
            ])
            ->add('customerEmail', EmailType::class, [
                'required' => true,
            ]);

        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
