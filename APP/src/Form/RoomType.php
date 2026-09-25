<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'constraints' => [new NotBlank(message: 'Bitte einen Namen eingeben.')]])
            ->add('externalId', TextType::class, ['label' => 'Kürzel auf der Website', 'required' => false, 'help' => 'So heißt der Raum in WordPress (z. B. „schwarz“). Darüber werden Vorstellungen dem Raum zugeordnet.'])
            ->add('position', IntegerType::class, ['label' => 'Reihenfolge im Kalender', 'help' => 'Kleinere Zahlen stehen weiter oben.'])
            ->add('showOnDashboard', CheckboxType::class, ['label' => 'Immer im Kalender zeigen', 'required' => false, 'help' => 'Sonst erscheint der Raum nur, wenn in der Woche etwas darin stattfindet.']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Room::class]);
    }
}
