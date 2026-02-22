<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['required' => true])
            ->add('lastName', TextType::class, ['required' => true])
            ->add('phone', TelType::class, ['required' => true])
            ->add('email', EmailType::class, ['required' => true])
            ->add('paymentMethod', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Cash on delivery' => 'COD',
                    'Card (demo)' => 'CARD',
                ],
            ]);
    }
}