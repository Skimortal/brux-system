<?php

namespace App\Form;

use App\Entity\Cleaning;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CleaningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'cleaning.name',
                'required' => true,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'cleaning.address',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('cleaningType', TextType::class, [
                'label' => 'cleaning.cleaning_type',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cleaning::class,
        ]);
    }
}
