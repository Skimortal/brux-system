<?php

namespace App\Form;

use App\Entity\ProductionTechnician;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ProductionTechnicianType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'constraints' => [new NotBlank(message: 'Bitte einen Namen eingeben.')]])
            ->add('email', EmailType::class, ['label' => 'E-Mail', 'required' => false])
            ->add('phone', TelType::class, ['label' => 'Telefon', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductionTechnician::class]);
    }
}
