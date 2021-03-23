<?php

namespace App\Service\TreeNode;

use App\Entity\TreeNodeInterface;
use Exception;

class CycleDetector
{
    public function containsCycle(TreeNodeInterface $node): bool
    {

        try {

            $this->checkAncestors($node);


        } catch (Exception $exception) {
            return true;
        }


        return false;
    }


    /**
     * @param TreeNodeInterface $node
     * @param TreeNodeInterface[] $ancestors
     * @throws Exception
     */
    private function checkAncestors(TreeNodeInterface $node, array $ancestors = []) {
        $currentAncestor = $node->getTreeNodeParent();

        if (empty($currentAncestor)) {
            return;
        }

        foreach ($ancestors as $ancestor) {
            if ($currentAncestor->isEqualToTreeNode($ancestor)) {
                throw new Exception("CYCLE");
            }
        }

        $ancestors[] = $currentAncestor;
        $this->checkAncestors($currentAncestor, $ancestors);
    }

    private function checkDescendants(TreeNodeInterface $node, array $descendants = []) {

        foreach ($node->getTreeNodeChildren() as $currentDescendant) {
            foreach ($descendants as $descendant) {
                if ($currentDescendant->isEqualToTreeNode($descendant)) {
                    throw new Exception("CYCLE");
                }

                $descendants[] = $currentDescendant;

            }
        }

        foreach ($node->getTreeNodeChildren() as $currentDescendant) {
            $this->checkDescendants($currentDescendant, $descendants);
        }
    }
}