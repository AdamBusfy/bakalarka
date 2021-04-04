<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 */
class Notification
{
    const TYPE_DEFAULT = 0;
    const TYPE_IMPORT = 1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notifications")
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $type = self::TYPE_DEFAULT;

    /**
     * @ORM\Column(type="boolean")
     */
    private $status = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $message;

    /**
     * @ORM\Column(name="date_create", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $date_create;

    public function __construct()
    {
        $this->date_create = new DateTime();
    }

    /**
     * @return DateTime
     */
    public function getDateCreate(): DateTime
    {
        return $this->date_create;
    }

    /**
     * @param DateTime $date_create
     */
    public function setDateCreate(DateTime $date_create): void
    {
        $this->date_create = $date_create;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
