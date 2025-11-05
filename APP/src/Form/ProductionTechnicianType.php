<?php

namespace App\Form;

use App\Entity\ProductionTechnician;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionTechnicianType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'production_technician.name',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'production_technician.email',
                'required' => true,
            ])
            ->add('phone', TextType::class, [
                'label' => 'production_technician.phone',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionTechnician::class,
        ]);
    }
}
