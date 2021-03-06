<?php

declare(strict_types=1);

namespace App\Controller\EasyAdmin;

use App\Entity\Tenant\Order;
use App\Entity\Tenant\OrderItem;
use App\Entity\Tenant\OrderItemGroup;
use App\Form\Model\OrderGroup;
use function assert;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class OrderItemGroupController extends OrderItemController
{
    protected function createNewEntity(): OrderGroup
    {
        $order = $this->getEntity(Order::class);
        if (!$order instanceof Order) {
            throw new BadRequestHttpException('Order not found');
        }

        $model = new OrderGroup();
        $model->order = $order;

        $parent = $this->getEntity(OrderItem::class);
        if ($parent instanceof OrderItem) {
            $model->parent = $parent;
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    protected function persistEntity($entity): OrderItemGroup
    {
        $model = $entity;
        assert($model instanceof OrderGroup);

        $entity = new OrderItemGroup($model->order, $model->name);
        $entity->setParent($model->parent);

        parent::persistEntity($entity);

        return $entity;
    }
}
