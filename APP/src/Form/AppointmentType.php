<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Contact;
use App\Entity\Production;
use App\Entity\Room;
use App\Enum\AppointmentStatusEnum;
use App\Enum\AppointmentTypeEnum;
use App\Enum\CleaningArea;
use App\Enum\ContactRole;
use App\Enum\EventTypeEnum;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One form for all appointment types; sections are shown or hidden by the
 * appointment-form Stimulus controller and cleaned up by AppointmentManager.
 * With "staffing" it only edits the staffing of a performance.
 */
final class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Appointment|null $appointment */
        $appointment = $builder->getData();
        $staffing = $options['staffing'];

        if (!$staffing) {
            $builder
                ->add('type', EnumType::class, [
                    'class' => AppointmentTypeEnum::class,
                    'label' => 'Art des Termins',
                    'expanded' => true,
                    'choice_label' => static fn (AppointmentTypeEnum $t) => $t->shortLabel(),
                    'attr' => ['data-action' => 'change->appointment-form#update'],
                ])
                ->add('title', TextType::class, [
                    'label' => 'Titel',
                    'required' => false,
                    'row_attr' => ['data-appointment-form-target' => 'title'],
                ])
                ->add('production', EntityType::class, [
                    'class' => Production::class,
                    'label' => 'Produktion',
                    'required' => false,
                    'placeholder' => 'Produktion wählen',
                    'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('p')->orderBy('p.title', 'ASC'),
                    'choice_label' => 'title',
                    'choice_attr' => static fn (Production $p) => ['data-needs' => implode(' · ', $p->getRequirementLabels())],
                    'attr' => ['data-controller' => 'tom-select', 'data-action' => 'change->appointment-form#update'],
                    'row_attr' => ['data-appointment-form-target' => 'production'],
                ])
                ->add('room', EntityType::class, [
                    'class' => Room::class,
                    'label' => 'Raum',
                    'required' => false,
                    'placeholder' => 'Kein Raum (ganzes Haus)',
                    'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('r')->orderBy('r.position', 'ASC')->addOrderBy('r.name', 'ASC'),
                    'choice_label' => 'name',
                    'row_attr' => ['data-appointment-form-target' => 'room'],
                ])
                ->add('startDate', DateTimeType::class, [
                    'label' => 'Beginn',
                    'widget' => 'single_text',
                    'attr' => ['data-appointment-form-target' => 'start', 'data-action' => 'change->appointment-form#startChanged'],
                ])
                ->add('endDate', DateTimeType::class, [
                    'label' => 'Ende',
                    'widget' => 'single_text',
                    'attr' => ['data-appointment-form-target' => 'end'],
                ])
                ->add('allDay', CheckboxType::class, ['label' => 'Ganztägig', 'required' => false])
                ->add('cleaningContact', EntityType::class, [
                    'class' => Contact::class,
                    'label' => 'Reinigungskraft',
                    'required' => false,
                    'placeholder' => 'Reinigungskraft wählen',
                    'query_builder' => static fn (ContactRepository $r) => $r->queryByRole(ContactRole::CLEANING),
                    'choice_label' => static fn (Contact $c) => $c->getName() . ($c->getCompany() ? ' (' . $c->getCompany() . ')' : ''),
                    'row_attr' => ['data-appointment-form-target' => 'cleaning'],
                ])
                ->add('cleaningAreas', EnumType::class, [
                    'class' => CleaningArea::class,
                    'label' => 'Bereiche',
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                    'choice_label' => static fn (CleaningArea $a) => $a->label(),
                    'attr' => ['data-style' => 'checks'],
                    'row_attr' => ['data-appointment-form-target' => 'cleaning'],
                ]);

            if ($appointment === null || !$appointment->isInSeries()) {
                $builder
                    ->add('repeat', ChoiceType::class, [
                        'label' => 'Wiederholen',
                        'mapped' => false,
                        'required' => false,
                        'placeholder' => 'Nicht wiederholen',
                        'choices' => array_flip(Appointment::RECURRENCE_FREQUENCIES),
                        'attr' => ['data-action' => 'change->appointment-form#update', 'data-appointment-form-target' => 'repeat'],
                    ])
                    ->add('repeatUntil', DateType::class, [
                        'label' => 'Wiederholen bis',
                        'mapped' => false,
                        'required' => false,
                        'widget' => 'single_text',
                        'row_attr' => ['data-appointment-form-target' => 'repeatUntil'],
                    ]);
            } else {
                $builder->add('scope', ChoiceType::class, [
                    'label' => 'Änderungen übernehmen für',
                    'mapped' => false,
                    'expanded' => true,
                    'data' => 'single',
                    'choices' => ['nur diesen Termin' => 'single', 'alle Termine der Serie' => 'series'],
                ]);
            }
        }

        $builder
            ->add('eventType', EnumType::class, [
                'class' => EventTypeEnum::class,
                'label' => 'Ereignisart',
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'choice_label' => static fn (EventTypeEnum $t) => $t->label(),
                'row_attr' => ['data-appointment-form-target' => 'productionOnly'],
                'disabled' => $staffing,
            ])
            ->add('status', EnumType::class, [
                'class' => AppointmentStatusEnum::class,
                'label' => 'Status',
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'choice_label' => static fn (AppointmentStatusEnum $s) => $s->label(),
                'row_attr' => ['data-appointment-form-target' => 'productionOnly'],
            ])
            ->add('internalTechniciansAttending', CheckboxType::class, [
                'label' => 'Externe Techniker kommen',
                'required' => false,
                'row_attr' => ['data-appointment-form-target' => 'productionOnly'],
            ])
            ->add('appointmentTechnicians', CollectionType::class, [
                'entry_type' => AppointmentTechnicianType::class,
                'label' => 'Technik',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_name' => '__tech__',
            ])
            ->add('appointmentVolunteers', CollectionType::class, [
                'entry_type' => AppointmentVolunteerType::class,
                'label' => 'Freiwillige',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_name' => '__vol__',
            ])
            ->add('description', TextareaType::class, [
                'label' => $staffing ? 'Notiz' : 'Beschreibung',
                'required' => false,
                'attr' => ['rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'staffing' => false,
        ]);
        $resolver->setAllowedTypes('staffing', 'bool');
    }
}
