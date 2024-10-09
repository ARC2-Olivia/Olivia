<?php

namespace App\Repository\Trait;

use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

trait TranslatableHintsTrait
{
    protected function setTranslatableHints(Query &$query, string $locale): void
    {
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
    }
}