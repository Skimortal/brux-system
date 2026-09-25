<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\ContactCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

final class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'constraints' => [new NotBlank(message: 'Bitte einen Namen eingeben.')]])
            ->add('category', EntityType::class, [
                'class' => ContactCategory::class,
                'label' => 'Kategorie',
                'placeholder' => 'Kategorie wählen',
                'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
                'choice_label' => static fn (ContactCategory $c) => $c->getName() . ($c->getRole() ? ' · ' . $c->getRole()->label() : ''),
                'constraints' => [new NotNull(message: 'Bitte eine Kategorie wählen.')],
            ])
            ->add('company', TextType::class, ['label' => 'Firma / Organisation', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'E-Mail', 'required' => false, 'constraints' => [new Email(message: 'Keine gültige E-Mail-Adresse.')]])
            ->add('phone', TelType::class, ['label' => 'Telefon', 'required' => false, 'attr' => ['maxlength' => 20]])
            ->add('notes', TextareaType::class, ['label' => 'Notizen', 'required' => false, 'attr' => ['rows' => 4]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Contact::class]);
    }
}
