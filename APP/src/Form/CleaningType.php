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
            ->add('cleaningDate', DateTimeType::class, [
                'label' => 'cleaning.cleaning_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('cleaningType', TextType::class, [
                'label' => 'cleaning.cleaning_type',
                'required' => false,
            ])
            ->add('generalAreas', CheckboxType::class, [
                'label' => 'cleaning.general_areas',
                'required' => false,
            ])
            ->add('blackRoom', CheckboxType::class, [
                'label' => 'cleaning.black_room',
                'required' => false,
            ])
            ->add('whiteRoom', CheckboxType::class, [
                'label' => 'cleaning.white_room',
                'required' => false,
            ])
            ->add('backstageToilets', CheckboxType::class, [
                'label' => 'cleaning.backstage_toilets',
                'required' => false,
            ])
            ->add('dressingRoom', CheckboxType::class, [
                'label' => 'cleaning.dressing_room',
                'required' => false,
            ])
            ->add('backstageCorridor', CheckboxType::class, [
                'label' => 'cleaning.backstage_corridor',
                'required' => false,
            ])
            ->add('officeGroundFloor', CheckboxType::class, [
                'label' => 'cleaning.office_ground_floor',
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
