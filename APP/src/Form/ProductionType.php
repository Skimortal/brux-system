<?php

namespace App\Form;

use App\Entity\Production;
use App\Service\FieldOwnership;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Local production data. Fields owned by WordPress are only added for productions
 * that do not come from WordPress.
 */
final class ProductionType extends AbstractType
{
    public function __construct(private readonly FieldOwnership $ownership)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Production $production */
        $production = $builder->getData();

        if (!$this->ownership->isLocked($production, 'title')) {
            $builder->add('title', TextType::class, ['label' => 'Titel', 'constraints' => [new NotBlank(message: 'Bitte einen Titel eingeben.')]]);
        }
        if (!$this->ownership->isLocked($production, 'permalink')) {
            $builder->add('permalink', UrlType::class, ['label' => 'Link zur Website', 'required' => false, 'default_protocol' => 'https']);
        }

        $builder
            ->add('needsLightingTechnician', CheckboxType::class, ['label' => 'Licht', 'required' => false])
            ->add('needsSoundTechnician', CheckboxType::class, ['label' => 'Ton', 'required' => false])
            ->add('needsSetupTechnician', CheckboxType::class, ['label' => 'Aufbau', 'required' => false])
            ->add('grandstand', ChoiceType::class, [
                'label' => 'Bühnenform',
                'expanded' => true,
                'required' => false,
                'placeholder' => 'offen',
                'choices' => array_flip(Production::GRANDSTAND_LABELS),
            ])
            ->add('contactPersons', CollectionType::class, [
                'entry_type' => ProductionContactPersonType::class,
                'label' => 'Ansprechpersonen',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_name' => '__cp__',
            ])
            ->add('technicians', CollectionType::class, [
                'entry_type' => ProductionTechnicianType::class,
                'label' => 'Technik der Produktion',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_name' => '__pt__',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Production::class]);
    }
}
