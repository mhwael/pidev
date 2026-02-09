<?php

namespace App\Form;

use App\Entity\MessagesForum;
use App\Entity\SujetsForum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessagesForumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sujetsForum', EntityType::class, [
                'class' => SujetsForum::class,
                'choice_label' => 'titre',
            ])
            ->add('auteur_id', IntegerType::class)
            ->add('contenu', TextareaType::class)
            ->add('date_creation', DateTimeType::class)
            ->add('nombre_likes', IntegerType::class, ['required' => false, 'empty_data' => 0])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessagesForum::class,
        ]);
    }
}
