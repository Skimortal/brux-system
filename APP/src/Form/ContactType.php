<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\ContactCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'contact.first_name',
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'contact.email',
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'contact.phone',
            ])
            ->add('company', TextType::class, [
                'required' => false,
                'label' => 'contact.company',
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'contact.notes',
            ])
            ->add('category', EntityType::class, [
                'class' => ContactCategory::class,
                'choice_label' => 'name',
                'required' => true,
                'label' => 'contact.category',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
