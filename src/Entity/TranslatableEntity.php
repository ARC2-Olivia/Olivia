<?php

namespace App\Entity;

use Gedmo\Translatable\Translatable;
use Gedmo\Mapping\Annotation as Gedmo;

abstract class TranslatableEntity implements Translatable
{
    #[Gedmo\Locale]
    private ?string $locale = null;

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}