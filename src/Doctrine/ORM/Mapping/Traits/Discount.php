<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Mapping\Traits;

use Doctrine\ORM\Mapping as ORM;
use function is_numeric;
use Money\Money;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
trait Discount
{
    /**
     * @ORM\Embedded(class="Money\Money")
     */
    private ?Money $discount = null;

    public function isDiscounted(): bool
    {
        if (null === $this->discount) {
            return false;
        }

        // Doctrine create nullable embedded
        if (!is_numeric($this->discount->getAmount())) {
            return false;
        }

        return !$this->discount->isZero();
    }

    public function discount(?Money $discount = null): ?Money
    {
        if (null === $discount) {
            // Doctrine create nullable embedded
            if ($this->discount instanceof Money && !is_numeric($this->discount->getAmount())) {
                return null;
            }

            return $this->discount;
        }

        return $this->discount = $discount->absolute();
    }
}
