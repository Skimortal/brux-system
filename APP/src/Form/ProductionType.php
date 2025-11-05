<?php

namespace App\Form;

use App\Entity\Production;
use App\Enum\ProductionType as ProductionTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'production.type',
                'choices' => [
                    'production.type.individual' => ProductionTypeEnum::INDIVIDUAL,
                    'production.type.group' => ProductionTypeEnum::GROUP,
                ],
                'choice_value' => function (?ProductionTypeEnum $entity) {
                    return $entity?->value;
                },
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])

            // Group fields
            ->add('groupName', TextType::class, [
                'label' => 'production.group_name',
                'required' => false,
            ])
            ->add('mainContactName', TextType::class, [
                'label' => 'production.main_contact_name',
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'production.address',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'production.phone',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'production.email',
                'required' => false,
            ])
            ->add('mainContactFunction', TextType::class, [
                'label' => 'production.main_contact_function',
                'required' => false,
            ])

            // Individual person fields
            ->add('personName', TextType::class, [
                'label' => 'production.person_name',
                'required' => false,
            ])
            ->add('personAddress', TextareaType::class, [
                'label' => 'production.person_address',
                'required' => false,
            ])
            ->add('personPhone', TextType::class, [
                'label' => 'production.person_phone',
                'required' => false,
            ])
            ->add('personEmail', EmailType::class, [
                'label' => 'production.person_email',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Production::class,
        ]);
    }
}
