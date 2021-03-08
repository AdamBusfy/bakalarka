<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 */
class Category
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
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity=Item::class, mappedBy="category", orphanRemoval=true)
     */
    private $items;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\OneToMany(targetEntity=History::class, mappedBy="category", orphanRemoval=true)
     */
    private $histories;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->histories = new ArrayCollection();
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

    public function addCategory(self $category): self
    {
        if (!$this->children->contains($category)) {
            $this->children[] = $category;
            $category->setParent($this);
        }

        return $this;
    }

    public function removeCategory(self $category): self
    {
        if ($this->children->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getParent() === $this) {
                $category->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Item[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setCategory($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCategory() === $this) {
                $item->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @param Category[] $ancestors
     * @return Category[]
     */
    public function getAncestors(array $ancestors = []): array
    {
        $ancestors[] = $this;

        if (!empty($this->getParent())) {
            $ancestors = array_merge($ancestors, $this->getParent()->getAncestors());
        }

        return $ancestors;
    }

//    public function getPath(string $delimiter = ' > '): string
//    {
//        $ancestors = $this->getAncestors();
//
//        $names = array_map(
//            function (Category $category, int $index) {
//                return $category->getName();
//            },
//            $ancestors,
//            array_keys($ancestors)
//        );
//
//        return implode($delimiter, $names);
//    }

public function getIsActive(): ?bool
{
    return $this->isActive;
}

public function setIsActive(bool $isActive): self
{
    $this->isActive = $isActive;

    return $this;
}

/**
 * @return Collection|History[]
 */
public function getHistories(): Collection
{
    return $this->histories;
}

public function addHistory(History $history): self
{
    if (!$this->histories->contains($history)) {
        $this->histories[] = $history;
        $history->setCategory($this);
    }

    return $this;
}

public function removeHistory(History $history): self
{
    if ($this->histories->removeElement($history)) {
        // set the owning side to null (unless already changed)
        if ($history->getCategory() === $this) {
            $history->setCategory(null);
        }
    }

    return $this;
}
}
