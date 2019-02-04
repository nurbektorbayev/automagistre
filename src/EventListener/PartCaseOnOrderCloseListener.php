<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Doctrine\Registry;
use App\Entity\Landlord\CarModel;
use App\Entity\Landlord\Part;
use App\Entity\Landlord\PartCase;
use App\Entity\Tenant\Order;
use App\Entity\Tenant\OrderItemPart;
use App\Events;
use Doctrine\ORM\Query\Expr\Join;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class PartCaseOnOrderCloseListener implements EventSubscriberInterface
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::ORDER_CLOSED => 'onOrderClosed',
        ];
    }

    public function onOrderClosed(GenericEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Order) {
            throw new LogicException('Order expected.');
        }

        $car = $order->getCar();
        if (null === $car) {
            return;
        }

        $carModel = $car->getCarModel();
        if (!$carModel instanceof CarModel) {
            return;
        }

        $parts = $order->getItems(OrderItemPart::class);
        if (0 === \count($parts)) {
            return;
        }

        /** @var Part[] $parts */
        $parts = \array_map(function (OrderItemPart $orderItemPart) {
            return $orderItemPart->getPart();
        }, $parts);

        $em = $this->registry->manager(PartCase::class);

        $existed = $this->registry->repository(Part::class)->createQueryBuilder('entity')
            ->select('entity.id')
            ->join(PartCase::class, 'pc', Join::WITH, 'entity = pc.part')
            ->where('pc.part IN (:parts)')
            ->andWhere('pc.carModel = :carModel')
            ->getQuery()
            ->setParameter('carModel', $carModel)
            ->setParameter('parts', $parts)
            ->getScalarResult();
        $existed = \array_map('array_shift', $existed);
        $existed = \array_flip($existed);

        foreach ($parts as $part) {
            if (\array_key_exists($part->getId(), $existed)) {
                continue;
            }

            $em->persist(new PartCase($part, $carModel));
        }

        $em->flush();
    }
}