<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\KeyLoan;
use App\Entity\KeyManagement;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class KeyLoanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var KeyLoan $loan */
        $loan = $builder->getData();
        $kind = $loan->getUser() !== null ? 'user' : ($loan->getHolderName() !== null && $loan->getContact() === null ? 'other' : 'contact');

        $builder
            ->add('key', EntityType::class, [
                'class' => KeyManagement::class,
                'label' => 'Schlüssel',
                'placeholder' => 'Schlüssel wählen',
                'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('k')->andWhere('k.lost = false')->orderBy('k.name', 'ASC'),
                'choice_label' => static fn (KeyManagement $k) => $k->getName() . ($k->getRoomNames() !== '' ? ' · ' . $k->getRoomNames() : ''),
            ])
            ->add('holderKind', ChoiceType::class, [
                'label' => 'An wen',
                'mapped' => false,
                'expanded' => true,
                'data' => $kind,
                'choices' => ['Kontakt' => 'contact', 'Benutzer:in' => 'user', 'Andere Person' => 'other'],
                'attr' => ['data-action' => 'change->key-loan-form#update'],
            ])
            ->add('contact', EntityType::class, [
                'class' => Contact::class,
                'label' => 'Kontakt',
                'required' => false,
                'placeholder' => 'Kontakt suchen',
                'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('c')->innerJoin('c.category', 'cat')->addSelect('cat')->orderBy('c.name', 'ASC'),
                'group_by' => static fn (Contact $c) => $c->getCategory()?->getName(),
                'choice_label' => static fn (Contact $c) => $c->getName() . ($c->getCompany() ? ' (' . $c->getCompany() . ')' : ''),
                'attr' => ['data-controller' => 'tom-select'],
                'row_attr' => ['data-key-loan-form-target' => 'contact'],
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Benutzer:in',
                'required' => false,
                'placeholder' => 'Benutzer:in wählen',
                'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('u')->andWhere('u.isActive = true')->orderBy('u.lastName', 'ASC'),
                'choice_label' => 'fullname',
                'row_attr' => ['data-key-loan-form-target' => 'user'],
            ])
            ->add('holderName', TextType::class, [
                'label' => 'Name',
                'required' => false,
                'row_attr' => ['data-key-loan-form-target' => 'other'],
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'Ausgabe',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('dueAt', DateTimeType::class, [
                'label' => 'Rückgabe geplant',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Notiz',
                'required' => false,
                'attr' => ['rows' => 2],
            ]);

        // only keep the holder that matches the selected kind
        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event): void {
            /** @var KeyLoan $loan */
            $loan = $event->getData();
            $kind = $event->getForm()->get('holderKind')->getData();
            if ($kind !== 'contact') {
                $loan->setContact(null);
            }
            if ($kind !== 'user') {
                $loan->setUser(null);
            }
            if ($kind !== 'other') {
                $loan->setHolderName(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => KeyLoan::class]);
    }
}
