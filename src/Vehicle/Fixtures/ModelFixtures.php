<?php

declare(strict_types=1);

namespace App\Vehicle\Fixtures;

use App\Car\Entity\Model;
use App\Manufacturer\Entity\Manufacturer;
use App\Manufacturer\Fixtures\ManufacturerFixtures;
use function assert;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ModelFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            ManufacturerFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return ['landlord'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $manufacturer = $this->getReference('manufacturer-1');
        assert($manufacturer instanceof Manufacturer);

        $model = new Model();
        $model->name = 'GTR';
        $model->manufacturer = $manufacturer;

        $this->addReference('model-1', $model);

        $manager->persist($model);
        $manager->flush();
    }
}
