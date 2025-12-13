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
            ->add('rooms', EntityType::class, [
                'class' => Room::class,
                'choice_label' => 'name',
                'label' => 'Raum',
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'tom-select',
                    'data-placeholder' => 'Bitte auswählen (Optional)',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'key_management.name',
                'required' => true,
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
