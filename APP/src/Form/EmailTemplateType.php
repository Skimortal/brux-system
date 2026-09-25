<?php

namespace App\Form;

use App\Entity\EmailTemplate;
use App\Enum\EmailTemplateKey;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmailTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('templateKey', EnumType::class, ['class' => EmailTemplateKey::class, 'label' => 'Vorlage', 'choice_label' => static fn (EmailTemplateKey $k) => $k->label()])
            ->add('locale', ChoiceType::class, ['label' => 'Sprache', 'choices' => ['Deutsch' => 'de', 'Deutsch (Österreich)' => 'de_AT', 'Englisch' => 'en']])
            ->add('subjectTemplate', TextType::class, ['label' => 'Betreff'])
            ->add('htmlTemplate', TextareaType::class, ['label' => 'Inhalt (HTML)', 'attr' => ['rows' => 14, 'class' => 'mono'], 'help' => 'Twig-Variablen: {{ user.fullname }}, {{ resetUrl }}, {{ expiresAt|date("H:i") }}'])
            ->add('textTemplate', TextareaType::class, ['label' => 'Inhalt (Text, optional)', 'required' => false, 'attr' => ['rows' => 8, 'class' => 'mono']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => EmailTemplate::class]);
    }
}
