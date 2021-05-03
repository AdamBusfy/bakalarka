<?php

namespace App\Entity;

use App\Repository\HistoryRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HistoryRepository::class)
 */
class History
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Item::class, inversedBy="histories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $item;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="histories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity=Location::class, inversedBy="histories")
     */
    private $location;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="histories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(name="date_create", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $date_create;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     */
    private $price = 0.0;

    public function __construct()
    {
        $this->date_create = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }
}
