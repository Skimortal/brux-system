<?php

namespace App\Form;

use App\Entity\ProductionPrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ProductionPriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categoryLabel', TextType::class, ['label' => 'Kategorie', 'constraints' => [new NotBlank(message: 'Bitte eine Kategorie angeben.')]])
            ->add('priceLabel', TextType::class, ['label' => 'Preis (€)', 'required' => false, 'attr' => ['placeholder' => 'z. B. 16 oder 14 - 26,5']])
            ->add('parentReserved', IntegerType::class, ['label' => 'Kontingent', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductionPrice::class]);
    }
}
