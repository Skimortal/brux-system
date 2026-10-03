<?php

namespace App\Form;

use App\Entity\ProductionCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ProductionCategoryType extends AbstractType
{
    /** Calendar colours that read well on light and dark backgrounds. */
    public const COLORS = [
        '#e05252' => 'Rot',
        '#e08a2e' => 'Orange',
        '#d4b106' => 'Gelb',
        '#4caf50' => 'Grün',
        '#16a3a3' => 'Türkis',
        '#3b82f6' => 'Blau',
        '#7c5cd6' => 'Violett',
        '#d64f9e' => 'Pink',
        '#8d6e63' => 'Braun',
        '#78808c' => 'Grau',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'constraints' => [new NotBlank()]])
            ->add('color', ChoiceType::class, [
                'label' => 'Farbe im Kalender',
                'required' => false,
                'expanded' => true,
                'placeholder' => 'keine',
                'choices' => array_flip(self::COLORS),
                'choice_attr' => static fn (?string $color) => $color !== null ? ['style' => 'accent-color: ' . $color] : [],
                'help' => 'Vorstellungen von Produktionen mit dieser Kategorie werden im Kalender so hinterlegt.',
            ])
            ->add('slug', TextType::class, ['label' => 'Slug', 'required' => false, 'disabled' => true])
            ->add('externalId', IntegerType::class, ['label' => 'WordPress-ID', 'required' => false, 'disabled' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductionCategory::class]);
    }
}
