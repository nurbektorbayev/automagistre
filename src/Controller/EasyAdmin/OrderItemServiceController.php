<?php

declare(strict_types=1);

namespace App\Controller\EasyAdmin;

use App\Entity\Car;
use App\Entity\CarModel;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderItemService;
use App\Form\Model\OrderService;
use App\Manager\RecommendationManager;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class OrderItemServiceController extends OrderItemController
{
    /**
     * @var RecommendationManager
     */
    private $recommendationManager;

    public function __construct(RecommendationManager $recommendationManager)
    {
        $this->recommendationManager = $recommendationManager;
    }

    public function recommendAction(): RedirectResponse
    {
        if (!$this->request->isMethod('POST')) {
            throw new BadRequestHttpException();
        }

        $query = $this->request->query;

        $orderItemService = $this->em->getRepository(OrderItemService::class)->findOneBy(['id' => $query->get('id')]);
        if (null === $orderItemService) {
            throw new NotFoundHttpException();
        }

        if (null === $orderItemService->getWorker()) {
            $this->addFlash(
                'error',
                \sprintf(
                    'Перед перенесом работы "%s" в рекоммендации нужно выбрать исполнителя.',
                    $orderItemService->getService()
                )
            );

            return $this->redirectToReferrer();
        }

        $this->recommendationManager->recommend($orderItemService);

        return $this->redirectToReferrer();
    }

    protected function createNewEntity(): OrderService
    {
        $order = $this->getEntity(Order::class);
        if (!$order instanceof Order) {
            throw new BadRequestHttpException('Order not found');
        }

        $model = new OrderService();
        $model->order = $order;
        $model->worker = $order->getActiveWorker();

        $parent = $this->getEntity(OrderItem::class);
        if ($parent instanceof OrderItem) {
            $model->parent = $parent;
        }

        return $model;
    }

    /**
     * @param OrderService $model
     */
    protected function persistEntity($model): void
    {
        $entity = new OrderItemService($model->order, $model->service, $model->price, $this->getUser());
        $entity->setParent($model->parent);
        $entity->setWorker($model->worker);
        $entity->setWarranty($model->warranty);

        parent::persistEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function createListQueryBuilder(
        $entityClass,
        $sortDirection,
        $sortField = null,
        $dqlFilter = null
    ): QueryBuilder {
        $qb = parent::createListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter);

        $car = $this->getEntity(Car::class);
        if (!$car instanceof Car) {
            throw new LogicException('Car required.');
        }

        $qb->join('entity.order', 'orders')
            ->join('orders.car', 'car')
            ->andWhere('car = :car')
            ->setParameter('car', $car);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function renderTemplate($actionName, $templatePath, array $parameters = []): Response
    {
        if ('list' === $actionName) {
            $parameters['car'] = $this->getEntity(Car::class);
        }

        return parent::renderTemplate($actionName, $templatePath, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function autocompleteAction(): JsonResponse
    {
        $request = $this->request;

        $qb = $this->em->getRepository(OrderItemService::class)
            ->createQueryBuilder('entity')
            ->orderBy('COUNT(entity.service)', 'DESC')
            ->addOrderBy('entity.service', 'ASC')
            ->setMaxResults(15);

        $car = $this->getEntity(Car::class);
        $order = $this->getEntity(Order::class);
        if (null === $car && $order instanceof Order) {
            $car = $order->getCar();
        }

        if ($car instanceof Car && ($carModel = $car->getCarModel()) instanceof CarModel) {
            $qb->leftJoin('entity.order', 'order')
                ->leftJoin('order.car', 'car')
                ->andWhere('car.carModel = :carModel')
                ->setParameter('carModel', $carModel);
        }

        if ($request->query->getBoolean('textOnly')) {
            $qb->groupBy('entity.service');
        }

        foreach (\explode(' ', \trim($request->query->get('query'))) as $key => $searchString) {
            $searchString = \trim($searchString);
            if ('' === $searchString) {
                continue;
            }

            $key = ':search_'.$key;

            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('entity.service', $key)
            ));

            $qb->setParameter($key, '%'.$searchString.'%');
        }

        $data = \array_map(function (OrderItemService $entity) {
            $price = $entity->getPrice();

            return [
                'id' => $entity->getId(),
                'text' => \sprintf('%s (%s)', $entity->getService(), $this->formatMoney($price)),
                'price' => $this->formatMoney($price, true),
            ];
        }, $qb->getQuery()->getResult());

        return $this->json(['results' => $data]);
    }
}
