<?php

namespace App\Entity;

interface TreeNodeInterface
{
    public function getTreeNodeParent(): ?TreeNodeInterface;

    /**
     * @return TreeNodeInterface[]
     */
    public function getTreeNodeChildren(): array;

    public function isEqualToTreeNode(TreeNodeInterface $node): bool;
}