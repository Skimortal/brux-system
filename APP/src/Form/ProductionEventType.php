<?php

namespace App\Form;

use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Entity\ProductionTechnician;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductionEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'production_event.name',
                'required' => true,
            ])
            ->add('production', EntityType::class, [
                'label' => 'production_event.production',
                'class' => Production::class,
                'choice_label' => 'displayName',
                'required' => false,
            ])
            ->add('presenceStartDate', DateType::class, [
                'label' => 'production_event.presence_start_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('presenceEndDate', DateType::class, [
                'label' => 'production_event.presence_end_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('generalRehearsalDate', DateTimeType::class, [
                'label' => 'production_event.general_rehearsal_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('photoSessionDate', DateTimeType::class, [
                'label' => 'production_event.photo_session_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('keyHandoverDate', DateTimeType::class, [
                'label' => 'production_event.key_handover_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('keyReturnDate', DateTimeType::class, [
                'label' => 'production_event.key_return_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('trailer', TextType::class, [
                'label' => 'production_event.trailer',
                'required' => false,
            ])
            ->add('projectDescription', TextareaType::class, [
                'label' => 'production_event.project_description',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('infoTexts', TextareaType::class, [
                'label' => 'production_event.info_texts',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('desiredTicketPrices', TextareaType::class, [
                'label' => 'production_event.desired_ticket_prices',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('duration', TextType::class, [
                'label' => 'production_event.duration',
                'required' => false,
            ])
            ->add('creditsAndBios', TextareaType::class, [
                'label' => 'production_event.credits_and_bios',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('technicalRider', FileType::class, [
                'label' => 'production_event.technical_rider',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                    ])
                ],
            ])
            ->add('externalTechnicians', EntityType::class, [
                'label' => 'production_event.external_technicians',
                'class' => ProductionTechnician::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionEvent::class,
        ]);
    }
}
