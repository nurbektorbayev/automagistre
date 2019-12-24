<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Enum\CarTransmission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class CarTransmissionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => CarTransmission::all(),
            'choice_label' => fn (CarTransmission $transmission) => $transmission->toName(),
            'choice_value' => fn (CarTransmission $transmission) => $transmission->toId(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
