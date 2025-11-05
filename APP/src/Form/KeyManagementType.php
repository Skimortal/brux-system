<?php

namespace App\Form;

use App\Entity\KeyManagement;
use App\Enum\KeyStatus;
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
            ->add('borrowerName', TextType::class, [
                'label' => 'key_management.borrower_name',
                'required' => false,
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
            ->add('signature', TextType::class, [
                'label' => 'key_management.signature',
                'required' => false,
                'help' => 'key_management.signature_help',
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
