<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * User administration and own profile ("profile" hides roles and status).
 */
final class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Vorname', 'constraints' => [new NotBlank()]])
            ->add('lastName', TextType::class, ['label' => 'Nachname', 'constraints' => [new NotBlank()]])
            ->add('email', EmailType::class, ['label' => 'E-Mail', 'constraints' => [new NotBlank(), new Email()]])
            ->add('phoneNumber', TelType::class, ['label' => 'Telefon', 'required' => false])
            ->add('mobileNumber', TelType::class, ['label' => 'Mobil', 'required' => false])
            ->add('department', TextType::class, ['label' => 'Bereich', 'required' => false])
            ->add('position', TextType::class, ['label' => 'Funktion', 'required' => false]);

        if (!$options['profile']) {
            $roles = ['Mitarbeit (Kalender, Daten)' => 'ROLE_USER', 'Verwaltung (Löschen, Benutzer, Räume)' => 'ROLE_ADMIN'];
            if ($options['can_grant_superuser']) {
                $roles['Superadmin (E-Mail-Vorlagen, Rechte)'] = 'ROLE_SUPERUSER';
            }
            $builder
                ->add('username', TextType::class, ['label' => 'Benutzername', 'constraints' => [new NotBlank()]])
                ->add('role', ChoiceType::class, [
                    'label' => 'Rechte',
                    'mapped' => false,
                    'expanded' => true,
                    'choices' => $roles,
                    'data' => $options['current_role'],
                    'attr' => ['data-style' => 'checks'],
                ])
                ->add('isActive', CheckboxType::class, ['label' => 'Konto aktiv', 'required' => false]);
        }

        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => $options['password_required'],
            'first_options' => ['label' => $options['password_required'] ? 'Passwort' : 'Neues Passwort', 'help' => $options['password_required'] ? 'Mindestens 8 Zeichen.' : 'Leer lassen, um es nicht zu ändern.', 'attr' => ['autocomplete' => 'new-password']],
            'second_options' => ['label' => 'Passwort wiederholen', 'attr' => ['autocomplete' => 'new-password']],
            'invalid_message' => 'Die Passwörter stimmen nicht überein.',
            'constraints' => $options['password_required'] ? [new NotBlank(), new Length(min: 8)] : [new Length(min: 8)],
        ]);

        if ($options['profile']) {
            $builder->add('avatarFile', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Profilbild',
                'mapped' => false,
                'required' => false,
                'constraints' => [new \Symfony\Component\Validator\Constraints\Image(maxSize: '2M', mimeTypesMessage: 'Bitte ein Bild hochladen.')],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'profile' => false,
            'password_required' => false,
            'can_grant_superuser' => false,
            'current_role' => 'ROLE_USER',
        ]);
    }
}
