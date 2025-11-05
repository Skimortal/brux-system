<?php

namespace App\Form;

use App\Entity\VolunteerPayment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VolunteerPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'volunteer_payment.amount',
                'currency' => 'EUR',
                'required' => true,
            ])
            ->add('paymentDate', DateType::class, [
                'label' => 'volunteer_payment.payment_date',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('proofDocument', FileType::class, [
                'label' => 'volunteer_payment.proof_document',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                    ])
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'volunteer_payment.notes',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VolunteerPayment::class,
        ]);
    }
}
