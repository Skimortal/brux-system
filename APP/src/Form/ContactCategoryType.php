<?php

namespace App\Form;

use App\Entity\ContactCategory;
use App\Enum\ContactRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ContactCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'constraints' => [new NotBlank(message: 'Bitte einen Namen eingeben.')]])
            ->add('role', EnumType::class, [
                'class' => ContactRole::class,
                'label' => 'Rolle im Kalender',
                'required' => false,
                'placeholder' => 'keine',
                'choice_label' => static fn (ContactRole $r) => $r->label(),
                'help' => 'Kontakte dieser Kategorie erscheinen im Kalender als Auswahl für Technik, Freiwillige oder Reinigung. Jede Rolle kann nur eine Kategorie haben.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactCategory::class,
            'constraints' => [new UniqueEntity(fields: ['role'], message: 'Diese Rolle hat bereits eine andere Kategorie.', ignoreNull: true)],
        ]);
    }
}
