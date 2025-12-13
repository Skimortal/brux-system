<?php

namespace App\Form;

use App\Entity\EventCategory;
use App\Entity\Production;
use App\Entity\ProductionContactPerson;
use App\Entity\ProductionEvent;
use App\Entity\Room;
use App\Enum\EventReservationStatus;
use App\Enum\EventStatus;
use App\Repository\ProductionContactPersonRepository;
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
        /** @var Production|null $production */
        $production = $options['production'] ?? null;

        $builder
            ->add('production', EntityType::class, [
                'label' => 'production_event.production',
                'class' => Production::class,
                'choice_label' => 'displayName',
                'required' => true,
                'disabled' => true,
            ])
            ->add('eventIndex', IntegerType::class, [
                'label' => 'production_event.event_index',
                'required' => false,
                'disabled' => true,
            ])
            ->add('date', DateType::class, [
                'label' => 'production_event.date',
                'widget' => 'single_text',
                'required' => false,
                'disabled' => true,
            ])
            ->add('timeFrom', TextType::class, [
                'label' => 'production_event.time_from',
                'required' => false,
                'attr' => ['placeholder' => 'HH:MM'],
                'disabled' => true,
            ])
            ->add('timeTo', TextType::class, [
                'label' => 'production_event.time_to',
                'required' => false,
                'attr' => ['placeholder' => 'HH:MM'],
                'disabled' => true,
            ])
            ->add('room', EntityType::class, [
                'label' => 'production_event.room',
                'class' => Room::class,
                'choice_label' => 'name',
                'required' => false,
                'disabled' => true,
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
                'disabled' => true,
            ])
            ->add('contactPersons', EntityType::class, [
                'label' => 'production_event.contact_persons',
                'class' => ProductionContactPerson::class,
                'multiple' => true,
                'required' => false,
                'choice_label' => function (ProductionContactPerson $cp): string {
                    $parts = [$cp->getName()];
                    if ($cp->getEmail()) $parts[] = $cp->getEmail();
                    if ($cp->getPhone()) $parts[] = $cp->getPhone();
                    return implode(' • ', $parts);
                },
                'query_builder' => function (ProductionContactPersonRepository $repo) use ($production) {
                    $qb = $repo->createQueryBuilder('cp')
                        ->orderBy('cp.hauptansprechperson', 'DESC')
                        ->addOrderBy('cp.name', 'ASC');

                    if ($production) {
                        $qb->andWhere('cp.production = :p')->setParameter('p', $production);
                    } else {
                        // Keine Produktion => keine Optionen anzeigen
                        $qb->andWhere('1 = 0');
                    }

                    return $qb;
                },
                'attr' => [
                    'class' => 'tom-select',
                    'data-placeholder' => 'Bitte auswählen',
                ],
                'disabled' => $production === null,
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
                'disabled' => true,
            ])
            ->add('quota', IntegerType::class, [
                'label' => 'production_event.quota',
                'required' => false,
                'disabled' => true,
            ])
            ->add('incomingTotal', IntegerType::class, [
                'label' => 'production_event.incoming_total',
                'required' => false,
                'disabled' => true,
            ])
            ->add('freeSeats', IntegerType::class, [
                'label' => 'production_event.free_seats',
                'required' => false,
                'disabled' => true,
            ])
            ->add('reservationNote', TextareaType::class, [
                'label' => 'production_event.reservation_note',
                'required' => false,
                'attr' => ['rows' => 3],
                'disabled' => true,
            ])
            ->add('categories', EntityType::class, [
                'label' => 'production_event.categories',
                'class' => EventCategory::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'class' => 'tom-select',
                    'data-placeholder' => 'Bitte auswählen',
                ],
                'disabled' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionEvent::class,
            'production' => null,
        ]);

        $resolver->setAllowedTypes('production', [Production::class, 'null']);
    }
}
