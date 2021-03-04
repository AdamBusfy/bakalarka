<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ItemRepository::class)
 */
class Item
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Item::class, inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=Item::class, mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="items")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(message="Category cannot be empty")
     */
    private $category;

    /**
     * @ORM\Column(name="date_create", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $date_create;

    /**
     * @ORM\ManyToOne(targetEntity=Location::class, inversedBy="items")
     */
    private $location;

    public function __construct()
    {
        $this->date_create = new DateTime();
        $this->children = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addItem(self $item): self
    {
        if (!$this->children->contains($item)) {
            $this->children[] = $item;
            $item->setParent($this);
        }

        return $this;
    }

    public function removeItem(self $item): self
    {
        if ($this->children->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getParent() === $this) {
                $item->setParent(null);
            }
        }

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

    /**
     * @param Item[] $ancestors
     * @return Item[]
     */
    public function getAncestors(array $ancestors = []): array
    {
        $ancestors[] = $this;

        if (!empty($this->getParent())) {
            $ancestors = array_merge($ancestors, $this->getParent()->getAncestors());
        }

        return $ancestors;
    }
}
