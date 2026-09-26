<?php

namespace App\Form;

use App\Entity\EventPrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class EventPriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categoryLabel', TextType::class, ['label' => 'Kategorie', 'constraints' => [new NotBlank(message: 'Bitte eine Kategorie angeben.')]])
            ->add('priceLabel', TextType::class, ['label' => 'Preis (€)', 'required' => false])
            ->add('reservedSeats', IntegerType::class, ['label' => 'Kontingent', 'required' => false])
            ->add('incomingReservations', IntegerType::class, ['label' => 'Reserviert', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => EventPrice::class]);
    }
}
