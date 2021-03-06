<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use Doctrine\ORM\Mapping as ORM;
use Money\Money;

/**
 * @ORM\Entity
 */
class WalletTransaction extends Transaction
{
    /**
     * @var Wallet
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Tenant\Wallet")
     * @ORM\JoinColumn
     */
    private $recipient;

    public function __construct(Wallet $wallet, string $description, Money $money)
    {
        $this->recipient = $wallet;

        parent::__construct($description, $money);
    }

    public function getRecipient(): Wallet
    {
        return $this->recipient;
    }
}
