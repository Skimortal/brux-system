<?php

namespace App\Form;

use App\Entity\ProductionContactPerson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionContactPersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'production_contact_person.name',
                'required' => true,
            ])
            ->add('nachname', TextType::class, [
                'label' => 'production_contact_person.nachname',
                'required' => true,
            ])
            ->add('hauptansprechperson', CheckboxType::class, [
                'label' => 'production_contact_person.hauptansprechperson',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionContactPerson::class,
        ]);
    }
}
