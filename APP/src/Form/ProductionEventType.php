<?php

namespace App\Form;

use App\Entity\EventCategory;
use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Entity\Room;
use App\Enum\EventReservationStatus;
use App\Enum\EventStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('production', EntityType::class, [
                'label' => 'production_event.production',
                'class' => Production::class,
                'choice_label' => 'displayName',
                'required' => true,
            ])
            ->add('eventIndex', IntegerType::class, [
                'label' => 'production_event.event_index',
                'required' => false,
            ])
            ->add('date', DateType::class, [
                'label' => 'production_event.date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('timeFrom', TextType::class, [
                'label' => 'production_event.time_from',
                'required' => false,
                'attr' => ['placeholder' => 'HH:MM'],
            ])
            ->add('timeTo', TextType::class, [
                'label' => 'production_event.time_to',
                'required' => false,
                'attr' => ['placeholder' => 'HH:MM'],
            ])
            ->add('room', EntityType::class, [
                'label' => 'production_event.room',
                'class' => Room::class,
                'choice_label' => 'name',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'production_event.status',
                'choices' => [
                    'event.status.active' => EventStatus::ACTIVE,
                    'event.status.inactive' => EventStatus::INACTIVE,
                ],
                'choice_value' => function (?EventStatus $entity) {
                    return $entity?->value;
                },
                'required' => false,
            ])
            ->add('reservationStatus', ChoiceType::class, [
                'label' => 'production_event.reservation_status',
                'choices' => [
                    'event.reservation_status.active' => EventReservationStatus::ACTIVE,
                    'event.reservation_status.inactive' => EventReservationStatus::INACTIVE,
                ],
                'choice_value' => function (?EventReservationStatus $entity) {
                    return $entity?->value;
                },
                'required' => false,
            ])
            ->add('quota', IntegerType::class, [
                'label' => 'production_event.quota',
                'required' => false,
            ])
            ->add('incomingTotal', IntegerType::class, [
                'label' => 'production_event.incoming_total',
                'required' => false,
            ])
            ->add('freeSeats', IntegerType::class, [
                'label' => 'production_event.free_seats',
                'required' => false,
            ])
            ->add('reservationNote', TextareaType::class, [
                'label' => 'production_event.reservation_note',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('categories', EntityType::class, [
                'label' => 'production_event.categories',
                'class' => EventCategory::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionEvent::class,
        ]);
    }
}
