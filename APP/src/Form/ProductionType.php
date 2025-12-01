<?php

namespace App\Form;

use App\Entity\Production;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('externalId', TextType::class, [
                'label' => 'production.external_id',
                'required' => false,
                'disabled' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'production.title',
                'required' => true,
                'disabled' => true,
            ])
            ->add('permalink', UrlType::class, [
                'label' => 'production.permalink',
                'required' => false,
                'disabled' => true,
            ])
            ->add('postThumbnailUrl', UrlType::class, [
                'label' => 'production.post_thumbnail_url',
                'required' => false,
                'disabled' => true,
            ])
            ->add('contentHtml', TextareaType::class, [
                'label' => 'production.content_html',
                'required' => false,
                'attr' => ['rows' => 10],
                'disabled' => true,
            ])
            ->add('excerptHtml', TextareaType::class, [
                'label' => 'production.excerpt_html',
                'required' => false,
                'attr' => ['rows' => 5],
                'disabled' => true,
            ])
            ->add('technicians', CollectionType::class, [
                'entry_type' => ProductionTechnicianType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('contactPersons', CollectionType::class, [
                'entry_type' => ProductionContactPersonType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
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
