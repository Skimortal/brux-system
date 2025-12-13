<?php

namespace App\Form;

use App\DTO\ProductionFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('showAll', CheckboxType::class, [
            'required' => false,
            'label' => 'Alle anzeigen',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionFilter::class,
            'method' => 'GET',
            'csrf_protection' => false, // für reine GET-Filter üblich
        ]);
    }
}
