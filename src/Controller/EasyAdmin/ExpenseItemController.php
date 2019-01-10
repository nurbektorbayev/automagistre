<?php

declare(strict_types=1);

namespace App\Controller\EasyAdmin;

use App\Entity\Tenant\Expense;
use App\Entity\Tenant\ExpenseItem;
use App\Events;
use App\Manager\PaymentManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class ExpenseItemController extends AbstractController
{
    /**
     * @var PaymentManager
     */
    private $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    protected function createNewEntity(): \stdClass
    {
        /** @var \stdClass $model */
        $model = parent::createNewEntity();

        $expense = $this->getEntity(Expense::class);
        if ($expense instanceof Expense) {
            $model->expense = $expense;
            $model->wallet = $expense->getWallet();
        }

        return $model;
    }

    /**
     * @param \stdClass $model
     */
    protected function persistEntity($model): void
    {
        $entity = $this->em->transactional(function (EntityManagerInterface $em) use ($model): ExpenseItem {
            $entity = new ExpenseItem($model->expense, $model->amount, $this->getUser(), $model->description);
            $em->persist($entity);

            $expense = $entity->getExpense();

            $description = \sprintf('# Списание по статье расходов - "%s"', $expense->getName());

            $this->paymentManager->createPayment($model->wallet, $description, $entity->getAmount()->negative());

            return $entity;
        });

        $this->event(Events::EXPENSE_ITEM_CREATED, $entity);

        $this->setReferer($this->generateEasyPath($entity, 'list'));
    }
}