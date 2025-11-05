<?php

namespace App\Form;

use App\Entity\Volunteer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VolunteerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'volunteer.name',
                'required' => true,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'volunteer.address',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('payments', CollectionType::class, [
                'label' => 'volunteer.payments',
                'entry_type' => VolunteerPaymentType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Volunteer::class,
        ]);
    }
}
