<?php

namespace App\Form;

use App\Entity\Cleaning;
use App\Entity\KeyManagement;
use App\Entity\Production;
use App\Entity\Room;
use App\Entity\Technician;
use App\Entity\User;
use App\Enum\KeyStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeyManagementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'choice_label' => 'name',
                'label' => 'Raum',
                'placeholder' => 'Bitte wählen (Optional)',
                'required' => false,
            ])
            ->add('keyColor', TextType::class, [
                'label' => 'key_management.key_color',
                'required' => true,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'key_management.status',
                'choices' => [
                    'key_management.status.available' => KeyStatus::AVAILABLE,
                    'key_management.status.borrowed' => KeyStatus::BORROWED,
                    'key_management.status.lost' => KeyStatus::LOST,
                ],
                'required' => true,
            ])
            // Entleiher Optionen
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email', // oder name
                'label' => 'Benutzer',
                'required' => false,
                'placeholder' => 'Kein Benutzer',
            ])
            ->add('technician', EntityType::class, [
                'class' => Technician::class,
                'choice_label' => 'name',
                'label' => 'Techniker',
                'required' => false,
                'placeholder' => 'Kein Techniker',
            ])
            ->add('production', EntityType::class, [
                'class' => Production::class,
                'choice_label' => function (Production $production) {
                    return $production->getDisplayName();
                },
                'label' => 'Produktion',
                'required' => false,
                'placeholder' => 'Keine Produktion',
            ])
            ->add('cleaning', EntityType::class, [
                'class' => Cleaning::class,
                'choice_label' => 'id', // Anpassen je nach Cleaning Entity
                'label' => 'Reinigung',
                'required' => false,
                'placeholder' => 'Keine Reinigung',
            ])
            ->add('borrowDate', DateType::class, [
                'label' => 'key_management.borrow_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('returnDate', DateType::class, [
                'label' => 'key_management.return_date',
                'widget' => 'single_text',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KeyManagement::class,
        ]);
    }
}
