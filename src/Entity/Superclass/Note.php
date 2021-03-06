<?php

declare(strict_types=1);

namespace App\Entity\Superclass;

use App\Doctrine\ORM\Mapping\Traits\CreatedAt;
use App\Doctrine\ORM\Mapping\Traits\Identity;
use App\Enum\NoteType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 */
abstract class Note
{
    use Identity;
    use CreatedAt;

    /**
     * @Assert\NotBlank
     *
     * @ORM\Column(type="note_type_enum", nullable=false)
     */
    public ?NoteType $type = null;

    /**
     * @Assert\NotBlank
     *
     * @ORM\Column(type="text")
     */
    public ?string $text = null;

    public function __construct(NoteType $type = null, string $text = null)
    {
        $this->type = $type;
        $this->text = $text;
    }

    public function __toString(): string
    {
        return $this->text ?? '...';
    }
}
