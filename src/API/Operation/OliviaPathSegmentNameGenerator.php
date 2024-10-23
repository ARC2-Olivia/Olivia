<?php

namespace App\API\Operation;

use ApiPlatform\Core\Util\Inflector;
use ApiPlatform\Operation\PathSegmentNameGeneratorInterface;

class OliviaPathSegmentNameGenerator implements PathSegmentNameGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        $pluralize = $collection;
        $newName = 'Course' === $name ? 'TheoreticalSubmodule' : $name;
        if ('ProofOfCompletion' === $newName) {
            $newName = 'ProofsOfCompletion';
            $pluralize = false;
        }
        if ($pluralize) {
            $newName = Inflector::pluralize($newName);
        }
        return Inflector::tableize($newName);
    }
}