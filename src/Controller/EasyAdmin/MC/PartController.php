<?php

declare(strict_types=1);

namespace App\Controller\EasyAdmin\MC;

use App\Controller\EasyAdmin\AbstractController;
use App\Entity\Landlord\MC\Line;
use App\Entity\Landlord\MC\Part;
use function assert;
use LogicException;
use stdClass;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class PartController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    protected function createNewEntity(): stdClass
    {
        $model = new stdClass();

        $line = $this->getEntity(Line::class);
        if (!$line instanceof Line) {
            throw new LogicException('Line required.');
        }

        $model->id = null;
        $model->line = $line;
        $model->part = null;
        $model->quantity = null;
        $model->recommended = null;

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    protected function persistEntity($entity): Part
    {
        $model = $entity;
        assert($model instanceof stdClass);

        $entity = new Part($model->line, $model->part, $model->quantity, $model->recommended);

        parent::persistEntity($entity);

        return $entity;
    }
}
