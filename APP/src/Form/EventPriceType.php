<?php

namespace App\Form;

use App\Entity\EventPrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Seats of one price of a performance. Category and price belong to the production
 * (WordPress keeps them there), so they are only shown.
 */
final class EventPriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categoryLabel', TextType::class, ['label' => 'Kategorie', 'disabled' => true])
            ->add('priceLabel', TextType::class, ['label' => 'Preis (€)', 'disabled' => true])
            ->add('reservedSeats', IntegerType::class, ['label' => 'Kontingent', 'required' => false])
            ->add('incomingReservations', IntegerType::class, ['label' => 'Reserviert', 'disabled' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => EventPrice::class]);
    }
}
