<?php

namespace App\Form;

use App\Entity\KeyManagement;
use App\Entity\Room;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class KeyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Bezeichnung', 'constraints' => [new NotBlank(message: 'Bitte eine Bezeichnung eingeben.')]])
            ->add('rooms', EntityType::class, [
                'class' => Room::class,
                'label' => 'Öffnet',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('r')->orderBy('r.position', 'ASC')->addOrderBy('r.name', 'ASC'),
                'choice_label' => 'name',
                'by_reference' => false,
            ])
            ->add('description', TextareaType::class, ['label' => 'Beschreibung', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('lost', CheckboxType::class, ['label' => 'Verloren', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => KeyManagement::class]);
    }
}
