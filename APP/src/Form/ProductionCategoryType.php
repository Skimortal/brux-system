<?php

namespace App\Form;

use App\Entity\ProductionCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ProductionCategoryType extends AbstractType
{
    private const NONE = 'none';
    private const OWN = 'own';

    /** Calendar colours that read well on light and dark backgrounds. */
    public const COLORS = [
        '#e05252' => 'Rot',
        '#e08a2e' => 'Orange',
        '#d4b106' => 'Gelb',
        '#4caf50' => 'Grün',
        '#16a3a3' => 'Türkis',
        '#3b82f6' => 'Blau',
        '#7c5cd6' => 'Violett',
        '#d64f9e' => 'Pink',
        '#8d6e63' => 'Braun',
        '#78808c' => 'Grau',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // a colour outside the palette (from WordPress or picked) is edited as the own colour
        $current = $builder->getData()?->getColor();
        $own = $current !== null && !isset(self::COLORS[$current]);

        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'constraints' => [new NotBlank()]])
            ->add('palette', ChoiceType::class, [
                'label' => 'Farbe',
                'mapped' => false,
                'required' => false,
                'expanded' => true,
                'placeholder' => false,
                'choices' => ['keine' => self::NONE] + array_flip(self::COLORS) + ['Eigene Farbe' => self::OWN],
                'data' => $current === null ? self::NONE : ($own ? self::OWN : $current),
                'attr' => ['data-style' => 'swatches'],
                'help' => 'Signalfarbe auf der Website. Im Kalender werden Vorstellungen von Produktionen mit dieser Kategorie so hinterlegt.',
            ])
            ->add('ownColor', ColorType::class, [
                'label' => 'Eigene Farbe',
                'mapped' => false,
                'required' => false,
                'data' => $own ? $current : '#000000',
                'attr' => ['data-swatches-target' => 'picker', 'data-action' => 'input->swatches#pick'],
            ])
            ->add('slug', TextType::class, ['label' => 'Slug', 'required' => false, 'disabled' => true, 'help' => 'Wird beim Anlegen aus dem Namen gebildet und bleibt dann gleich.'])
            ->add('externalId', IntegerType::class, ['label' => 'WordPress-ID', 'required' => false, 'disabled' => true]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $form = $event->getForm();
            $choice = $form->get('palette')->getData();
            $color = match ($choice) {
                self::NONE, null => null,
                self::OWN => strtolower((string) $form->get('ownColor')->getData()),
                default => $choice,
            };
            if ($color !== null && !preg_match('/^#[0-9a-f]{6}$/', $color)) {
                $form->get('ownColor')->addError(new FormError('Bitte eine Farbe wählen.'));

                return;
            }
            $event->getData()->setColor($color);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductionCategory::class]);
    }
}
