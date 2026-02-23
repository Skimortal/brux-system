<?php

namespace App\Form;

use App\Entity\CleaningSchedule;
use App\Entity\Cleaning;
use App\Entity\Contact;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CleaningScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cleaningContact', EntityType::class, [
                'class' => Contact::class,
                'choice_label' => 'name',
                'label' => 'Reinigungsfirma',
            ])
            ->add('weekdays', ChoiceType::class, [
                'label' => 'Wochentage',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Montag' => 1,
                    'Dienstag' => 2,
                    'Mittwoch' => 3,
                    'Donnerstag' => 4,
                    'Freitag' => 5,
                    'Samstag' => 6,
                    'Sonntag' => 7,
                ],
            ])
            ->add('timeFrom', TimeType::class, [
                'label' => 'Startzeit',
                'input' => 'datetime',
                'widget' => 'single_text',
            ])
            ->add('timeTo', TimeType::class, [
                'label' => 'Endzeit',
                'input' => 'datetime',
                'widget' => 'single_text',
            ])
            ->add('activeFrom', DateType::class, [
                'label' => 'Gültig ab',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('activeTo', DateType::class, [
                'label' => 'Gültig bis',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CleaningSchedule::class,
        ]);
    }
}
