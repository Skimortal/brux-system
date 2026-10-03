<?php

namespace App\Form;

use App\Entity\Production;
use App\Entity\ProductionCategory;
use App\Service\FieldOwnership;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Local production data. Fields owned by WordPress are only added for productions
 * that do not come from WordPress.
 */
final class ProductionType extends AbstractType
{
    private const SECTIONS = [
        'generalInfo' => 'Allgemeine Infos',
        'shortDescription' => 'Kurzbeschreibung',
        'description' => 'Stückbeschreibung',
        'pressQuotes' => 'Pressestimmen',
        'credits' => 'Credits',
        'additionalInfo' => 'Zusätzliche Informationen',
    ];

    public function __construct(private readonly FieldOwnership $ownership)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Production $production */
        $production = $builder->getData();

        $locked = fn (string $field): bool => $this->ownership->isLocked($production, $field);

        // website content: disabled while WordPress owns it
        $builder
            ->add('title', TextType::class, ['label' => 'Titel', 'disabled' => $locked('title'), 'constraints' => [new NotBlank(message: 'Bitte einen Titel eingeben.')]])
            ->add('websiteStatus', ChoiceType::class, [
                'label' => 'Auf der Website',
                'expanded' => true,
                'choices' => array_flip(Production::WEBSITE_STATUS_LABELS),
                'disabled' => $locked('websiteStatus'),
            ])
            ->add('permalink', UrlType::class, ['label' => 'Link zur Website', 'required' => false, 'default_protocol' => 'https', 'disabled' => $locked('permalink')])
            ->add('postThumbnailUrl', UrlType::class, ['label' => 'Bild-URL', 'required' => false, 'default_protocol' => 'https', 'disabled' => $locked('postThumbnailUrl')])
            ->add('imageFile', FileType::class, [
                'label' => 'Bild hochladen',
                'mapped' => false,
                'required' => false,
                'disabled' => $locked('postThumbnailUrl'),
                'help' => 'JPG, PNG oder WebP, höchstens 8 MB. Ersetzt die Bild-URL.',
                'constraints' => [new Image(maxSize: '8M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'], mimeTypesMessage: 'Bitte ein JPG-, PNG- oder WebP-Bild hochladen.')],
            ])
            ->add('subtitle', TextType::class, [
                'label' => 'Untertitel',
                'required' => false,
                'disabled' => $locked('contentHtml'),
                'help' => 'Gruppe bzw. Künstler*in und Gattung, z. B. „von OFFTANZ Tirol | Zeitgenössischer Tanz“',
            ]);
        // sections of the website post, each a paragraph of the post template
        foreach (self::SECTIONS as $field => $label) {
            $builder->add($field, TextareaType::class, [
                'label' => $label,
                'required' => false,
                'disabled' => $locked('contentHtml'),
                'sanitize_html' => true,
                'sanitizer' => 'app.website_content',
            ]);
        }
        $builder
            ->add('excerptHtml', TextareaType::class, ['label' => 'Kurztext (Auszug)', 'required' => false, 'attr' => ['rows' => 3], 'disabled' => $locked('excerptHtml'), 'help' => 'Wird auf der Website derzeit nicht angezeigt.'])
            ->add('categories', EntityType::class, [
                'class' => ProductionCategory::class,
                'label' => 'Kategorien',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => 'name',
                'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
                'by_reference' => false,
                'disabled' => $locked('categories'),
            ])
            ->add('priceList', CollectionType::class, [
                'entry_type' => ProductionPriceType::class,
                'label' => 'Preise',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_name' => '__price__',
                'disabled' => $locked('priceList'),
            ]);

        $builder
            ->add('needsLightingTechnician', CheckboxType::class, ['label' => 'Licht', 'required' => false])
            ->add('needsSoundTechnician', CheckboxType::class, ['label' => 'Ton', 'required' => false])
            ->add('needsSetupTechnician', CheckboxType::class, ['label' => 'Aufbau', 'required' => false])
            ->add('grandstand', ChoiceType::class, [
                'label' => 'Bühnenform',
                'expanded' => true,
                'required' => false,
                'placeholder' => 'offen',
                'choices' => array_flip(Production::GRANDSTAND_LABELS),
            ])
            ->add('contactPersons', CollectionType::class, [
                'entry_type' => ProductionContactPersonType::class,
                'label' => 'Ansprechpersonen',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_name' => '__cp__',
            ])
            ->add('technicians', CollectionType::class, [
                'entry_type' => ProductionTechnicianType::class,
                'label' => 'Technik der Produktion',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_name' => '__pt__',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Production::class]);
    }
}
