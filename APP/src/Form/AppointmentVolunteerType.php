<?php

namespace App\Form;

use App\Entity\AppointmentVolunteer;
use App\Entity\Contact;
use App\Enum\ContactRole;
use App\Enum\VolunteerTaskEnum;
use App\Repository\ContactRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

final class AppointmentVolunteerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contact', EntityType::class, [
                'class' => Contact::class,
                'label' => false,
                'placeholder' => 'Freiwillige:n wählen',
                'query_builder' => static fn (ContactRepository $r) => $r->queryByRole(ContactRole::VOLUNTEER),
                'choice_label' => 'name',
                'constraints' => [new NotNull(message: 'Bitte eine Person wählen.')],
            ])
            ->add('confirmed', CheckboxType::class, ['label' => 'bestätigt', 'required' => false])
            ->add('taskEnums', EnumType::class, [
                'class' => VolunteerTaskEnum::class,
                'label' => false,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (VolunteerTaskEnum $t) => $t->label(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AppointmentVolunteer::class]);
    }
}
