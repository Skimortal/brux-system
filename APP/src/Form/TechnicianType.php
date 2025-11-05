<?php

namespace App\Form;

use App\Entity\Technician;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TechnicianType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'technician.name',
                'required' => true,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'technician.address',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('email', EmailType::class, [
                'label' => 'technician.email',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'technician.phone',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'technician.notes',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Technician::class,
        ]);
    }
}
