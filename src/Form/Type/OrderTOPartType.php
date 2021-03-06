<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\Model\OrderTOPart;
use App\Manager\PartManager;
use function count;
use LogicException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class OrderTOPartType extends AbstractType
{
    private PartManager $partManager;

    public function __construct(PartManager $partManager)
    {
        $this->partManager = $partManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('selected', CheckboxType::class, [
                'label' => 'Выбрать',
                'required' => false,
            ])
            ->add('price', MoneyType::class)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                $form = $event->getForm();
                $model = $event->getData();
                if (!$model instanceof OrderTOPart) {
                    throw new LogicException('OrderTOPart expected.');
                }

                $part = $model->part;

                $analogs = $this->partManager->crossesInStock($part);
                $hasAnalog = 0 < count($analogs);

                $choices = [$part];
                if ($hasAnalog) {
                    $choices = [...$choices, ...$analogs];
                }

                $form->add('part', ChoiceType::class, [
                    'label' => 'Запчасть',
                    'choices' => $choices,
                    'choice_label' => 'displayName',
                    'choice_value' => 'id',
                    'expanded' => false,
                    'multiple' => false,
                    'disabled' => !$hasAnalog,
                ]);
            })
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
                $form = $event->getForm();
                $model = $event->getData();
                if (!$model instanceof OrderTOPart) {
                    throw new LogicException('OrderTOPart expected.');
                }

                $price = $form->get('price');
                if (null === $price->getData()) {
                    $price->setData($this->partManager->suggestPrice($model->part));
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => OrderTOPart::class,
            ]);
    }
}
