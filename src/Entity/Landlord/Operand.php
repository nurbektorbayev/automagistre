<?php

declare(strict_types=1);

namespace App\Entity\Landlord;

use App\Doctrine\ORM\Mapping\Traits\Identity;
use App\Entity\Tenant\OperandTransaction;
use App\Entity\Transactional;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"1": "Person", "2": "Organization"})
 */
abstract class Operand implements Transactional
{
    use Identity;

    /**
     * @ORM\Column(type="uuid", unique=true)
     */
    public UuidInterface $uuid;

    /**
     * @Assert\Email
     *
     * @ORM\Column(nullable=true)
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $contractor = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $seller = false;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    abstract public function __toString(): string;

    abstract public function getFullName(): string;

    abstract public function getTelephone(): ?PhoneNumber;

    abstract public function isType(string $type): bool;

    public function getTransactionClass(): string
    {
        return OperandTransaction::class;
    }

    public function isEqual(?self $operand): bool
    {
        return null !== $operand && $operand->getId() === $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function isContractor(): bool
    {
        return $this->contractor;
    }

    public function setContractor(bool $contractor): void
    {
        $this->contractor = $contractor;
    }

    public function isSeller(): bool
    {
        return $this->seller;
    }

    public function setSeller(bool $seller): void
    {
        $this->seller = $seller;
    }
}
