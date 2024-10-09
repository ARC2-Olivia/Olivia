<?php

namespace App\Repository\Trait;

use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

trait FindOneByIdWithLocaleTrait
{
    use TranslatableHintsTrait;

    public function findOneByIdWithLocale(int $id, string $locale)
    {
        $query = $this->createQueryBuilder('x')->where('x.id = :id')->setParameter('id', $id)->setMaxResults(1)->getQuery();
        $this->setTranslatableHints($query, $locale);
        return $query->getOneOrNullResult();
    }
}