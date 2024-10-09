<?php

namespace App\API\Entity;


use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\API\State\CertificateStateProvider;
use App\Entity\Course;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['Certificate']])]
#[GetCollection(provider: CertificateStateProvider::class)]
class Certificate
{
    #[Groups('Certificate')]
    public ?string $title = null;

    #[Groups('Certificate')]
    public ?string $url = null;

    #[Groups('Certificate')]
    public ?Course $theoreticalSubmodule = null;
}