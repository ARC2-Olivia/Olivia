<?php

namespace App\Entity;

use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;

abstract class TranslatableEntity implements Translatable
{
    #[Gedmo\Locale]
    private ?String $locale = null;

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}