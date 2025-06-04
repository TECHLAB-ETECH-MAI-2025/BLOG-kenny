<?php

namespace App\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDeleteFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Vérifie si l'entité a la propriété isDeleted
        if (!$targetEntity->hasField('isDeleted')) {
            return '';
        }

        return sprintf('%s.is_deleted = false', $targetTableAlias);
    }
}
