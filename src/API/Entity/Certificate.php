<?php

namespace App\API\Entity;


use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\API\State\CertificateStateProvider;
use App\Entity\Course;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['api']])]
#[GetCollection(provider: CertificateStateProvider::class)]
class Certificate
{
    #[Groups('api')]
    public ?string $title = null;

    #[Groups('api')]
    public ?string $url = null;

    #[Groups('api')]
    public ?Course $theoreticalSubmodule = null;
}