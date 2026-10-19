<?php

namespace App\Form;

use App\Entity\AppointmentTechnician;
use App\Entity\Contact;
use App\Enum\ContactRole;
use App\Repository\ContactRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use App\Enum\TechnicianStatus;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

final class AppointmentTechnicianType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contact', EntityType::class, [
                'class' => Contact::class,
                'label' => false,
                'placeholder' => 'Techniker:in wählen',
                'query_builder' => static fn (ContactRepository $r) => $r->queryByRole(ContactRole::TECHNICIAN),
                'choice_label' => static fn (Contact $c) => $c->getName() . ($c->getCompany() ? ' (' . $c->getCompany() . ')' : ''),
                'constraints' => [new NotNull(message: 'Bitte eine Person wählen.')],
            ])
            ->add('status', EnumType::class, [
                'class' => TechnicianStatus::class,
                // a new row starts as "eingeteilt"; "Interesse" comes from the technicians themselves
                'choices' => [TechnicianStatus::ASSIGNED, TechnicianStatus::ACCEPTED, TechnicianStatus::DECLINED, TechnicianStatus::CONFIRMED, TechnicianStatus::INTERESTED],
                'label' => false,
                'choice_label' => static fn (TechnicianStatus $s) => $s->label(),
                'attr' => ['aria-label' => 'Status'],
            ])
            ->add('lighting', CheckboxType::class, ['label' => 'Licht', 'required' => false])
            ->add('sound', CheckboxType::class, ['label' => 'Ton', 'required' => false])
            ->add('setup', CheckboxType::class, ['label' => 'Aufbau', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AppointmentTechnician::class]);
    }
}
