<?php

namespace App\Form;

use App\Entity\ProductionContactPerson;
use App\Entity\ProductionEvent;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contact persons of one performance, chosen from its production.
 */
final class PerformanceContactsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductionEvent $performance */
        $performance = $builder->getData();
        $builder->add('contactPersons', EntityType::class, [
            'class' => ProductionContactPerson::class,
            'label' => false,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'query_builder' => static fn (EntityRepository $r) => $r->createQueryBuilder('cp')
                ->andWhere('cp.production = :p')
                ->setParameter('p', $performance->getProduction())
                ->orderBy('cp.hauptansprechperson', 'DESC')
                ->addOrderBy('cp.name', 'ASC'),
            'choice_label' => static fn (ProductionContactPerson $cp) => $cp->getName() . ($cp->isHauptansprechperson() ? ' (Haupt)' : ''),
            'attr' => ['data-action' => 'change->autosubmit#submit'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductionEvent::class]);
    }
}
