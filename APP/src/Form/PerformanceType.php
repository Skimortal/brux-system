<?php

namespace App\Form;

use App\Entity\EventCategory;
use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Entity\Room;
use App\Enum\EventReservationStatus;
use App\Enum\EventStatus;
use App\Service\FieldOwnership;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Date, room, seats and prices of a performance. Fields that WordPress owns are
 * disabled for performances of WordPress productions (see FieldOwnership).
 */
final class PerformanceType extends AbstractType
{
    public function __construct(private readonly FieldOwnership $ownership)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductionEvent $performance */
        $performance = $builder->getData();
        $locked = fn (string $field): bool => $this->ownership->isLocked($performance, $field);
        $ownership = $this->ownership;
        // one row per price of the production, so the form shows what WordPress has
        $performance->alignPricesWithProduction();

        if ($performance->getId() === null) {
            $builder->add('production', EntityType::class, [
                'class' => Production::class,
                'label' => 'Produktion',
                'placeholder' => 'Produktion wählen',
                'query_builder' => static fn (EntityRepository $r) => $ownership->canCreatePerformances()
                    ? $r->createQueryBuilder('p')->orderBy('p.title', 'ASC')
                    : $r->createQueryBuilder('p')->andWhere('p.externalId IS NULL')->orderBy('p.title', 'ASC'),
                'choice_label' => 'title',
                'constraints' => [new NotNull(message: 'Bitte eine Produktion wählen.')],
                'attr' => ['data-controller' => 'tom-select'],
                'help' => $ownership->canCreatePerformances() ? null : 'Vorstellungen von WordPress-Produktionen werden derzeit in WordPress angelegt.',
            ]);
        }

        $builder
            ->add('date', DateType::class, ['label' => 'Datum', 'widget' => 'single_text', 'disabled' => $locked('date'), 'constraints' => [new NotNull(message: 'Bitte ein Datum angeben.')]])
            ->add('timeFrom', TimeType::class, ['label' => 'Beginn', 'widget' => 'single_text', 'input' => 'string', 'input_format' => 'H:i', 'with_seconds' => false, 'required' => false, 'disabled' => $locked('timeFrom')])
            ->add('timeTo', TimeType::class, ['label' => 'Ende', 'widget' => 'single_text', 'input' => 'string', 'input_format' => 'H:i', 'with_seconds' => false, 'required' => false, 'disabled' => $locked('timeTo')])
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'label' => 'Raum',
                'required' => false,
                'placeholder' => 'Kein Raum',
                'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('r')->orderBy('r.position', 'ASC'),
                'choice_label' => 'name',
                'disabled' => $locked('room'),
            ])
            ->add('status', EnumType::class, ['class' => EventStatus::class, 'label' => 'Status', 'expanded' => true, 'required' => false, 'placeholder' => false, 'choice_label' => static fn (EventStatus $s) => $s->label(), 'disabled' => $locked('status')])
            ->add('reservationStatus', EnumType::class, ['class' => EventReservationStatus::class, 'label' => 'Online-Reservierung', 'expanded' => true, 'required' => false, 'placeholder' => false, 'choice_label' => static fn (EventReservationStatus $s) => $s === EventReservationStatus::ACTIVE ? 'Aktiv' : 'Inaktiv', 'disabled' => $locked('reservationStatus')])
            ->add('quota', IntegerType::class, ['label' => 'Kontingent', 'required' => false, 'disabled' => $locked('quota')])
            ->add('incomingTotal', IntegerType::class, ['label' => 'Reserviert', 'required' => false, 'disabled' => $locked('incomingTotal')])
            ->add('freeSeats', IntegerType::class, ['label' => 'Frei', 'required' => false, 'disabled' => $locked('freeSeats')])
            ->add('reservationNote', TextType::class, ['label' => 'Reservierungshinweis oder -link', 'required' => false, 'disabled' => $locked('reservationNote')])
            ->add('categories', EntityType::class, [
                'class' => EventCategory::class,
                'label' => 'Kategorien der Vorstellung',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => 'name',
                'by_reference' => false,
                'disabled' => $locked('categories'),
            ])
            ->add('priceList', CollectionType::class, [
                'entry_type' => EventPriceType::class,
                'label' => 'Kontingent je Preis',
                'by_reference' => false,
                'prototype_name' => '__price__',
                'disabled' => $locked('priceList'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductionEvent::class]);
    }
}
