<?php

namespace App\Operation;

use ApiPlatform\Operation\PathSegmentNameGeneratorInterface;

class OliviaPathSegmentNameGenerator implements PathSegmentNameGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        $name = match ($name) {
            'Course' => 'TheoreticalSubmodule',
            default => $name
        };
        return \ApiPlatform\Core\Util\Inflector::tableize($name);
    }
}