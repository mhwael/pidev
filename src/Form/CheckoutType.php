<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// ✅ Validator constraints (control de saisie)
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
                'trim' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'First name is required.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'First name must be at least {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
                'trim' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Last name is required.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Last name must be at least {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'required' => true,
                'trim' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Phone is required.']),
                    new Assert\Regex([
                        'pattern' => '/^\d+$/',
                        'message' => 'Phone must contain only numbers.',
                    ]),
                    new Assert\Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => 'Phone must be at least {{ limit }} digits.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'trim' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email is required.']),
                    new Assert\Email(['message' => 'Email is not valid.']),
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'required' => true,
                'placeholder' => 'Choose payment method',
                'choices' => [
                    'Cash on delivery' => 'COD',
                    'Card (demo)' => 'CARD',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Payment method is required.']),
                    new Assert\Choice([
                        'choices' => ['COD', 'CARD'],
                        'message' => 'Invalid payment method.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // ✅ Not mapped to an entity: returns array data
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
        ]);
    }
}