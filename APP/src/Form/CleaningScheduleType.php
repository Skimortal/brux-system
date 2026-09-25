<?php

namespace App\Form;

use App\Entity\CleaningSchedule;
use App\Entity\Contact;
use App\Enum\ContactRole;
use App\Repository\ContactRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CleaningScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cleaningContact', EntityType::class, [
                'class' => Contact::class,
                'label' => 'Reinigungskraft',
                'placeholder' => 'Reinigungskraft wählen',
                'query_builder' => static fn (ContactRepository $r) => $r->queryByRole(ContactRole::CLEANING),
                'choice_label' => static fn (Contact $c) => $c->getName() . ($c->getCompany() ? ' (' . $c->getCompany() . ')' : ''),
                'help' => 'Kontakte der Kategorie mit der Rolle „Reinigung“.',
            ])
            ->add('weekdays', ChoiceType::class, [
                'label' => 'Wochentage',
                'multiple' => true,
                'expanded' => true,
                'choices' => array_flip(CleaningSchedule::WEEKDAYS),
            ])
            ->add('timeFrom', TimeType::class, ['label' => 'Von', 'widget' => 'single_text'])
            ->add('timeTo', TimeType::class, ['label' => 'Bis', 'widget' => 'single_text'])
            ->add('activeFrom', DateType::class, ['label' => 'Aktiv ab', 'widget' => 'single_text', 'required' => false])
            ->add('activeTo', DateType::class, ['label' => 'Aktiv bis', 'widget' => 'single_text', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CleaningSchedule::class]);
    }
}
