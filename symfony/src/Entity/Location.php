<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use App\Service\TreeNode\TreeNodeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LocationRepository::class)
 */
class Location implements TreeNodeInterface
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
     * @ORM\ManyToOne(targetEntity=Location::class, inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=Location::class, mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="locations")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity=Item::class, mappedBy="location")
     */
    private $items;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\OneToMany(targetEntity=History::class, mappedBy="location")
     */
    private $histories;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->users = new ArrayCollection();
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

    public function addLocation(self $location): self
    {
        if (!$this->children->contains($location)) {
            $this->children[] = $location;
            $location->setParent($this);
        }

        return $this;
    }

    public function removeLocation(self $location): self
    {
        if ($this->children->removeElement($location)) {
            // set the owning side to null (unless already changed)
            if ($location->getParent() === $this) {
                $location->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addLocation($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeLocation($this);
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
            $item->setLocation($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getLocation() === $this) {
                $item->setLocation(null);
            }
        }

        return $this;
    }

    /**
     * @param Location[] $ancestors
     * @return Location[]
     */
    public function getAncestors(array $ancestors = []): array
    {
        $ancestors[] = $this;

        if (!empty($this->getParent())) {
            $ancestors = array_merge($ancestors, $this->getParent()->getAncestors());
        }

        return $ancestors;
    }

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
            $history->setLocation($this);
        }

        return $this;
    }

    public function removeHistory(History $history): self
    {
        if ($this->histories->removeElement($history)) {
            // set the owning side to null (unless already changed)
            if ($history->getLocation() === $this) {
                $history->setLocation(null);
            }
        }

        return $this;
    }

    public function getTreeNodeParent(): ?TreeNodeInterface
    {
        return $this->getParent();
    }

    /**
     * @return TreeNodeInterface[]
     */
    public function getTreeNodeChildren(): array
    {
        return $this->getChildren()->toArray();
    }

    public function isEqualToTreeNode(TreeNodeInterface $node): bool
    {
        if (!$node instanceof Location) {
            return false;
        }

        return $this->id === $node->getId();
    }
}
