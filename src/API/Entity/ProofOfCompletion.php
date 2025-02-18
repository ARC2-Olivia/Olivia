<?php

namespace App\API\Entity;


use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\API\State\ProofOfCompletionStateProvider;
use App\Entity\Course;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['ProofOfCompletion']])]
#[GetCollection(provider: ProofOfCompletionStateProvider::class)]
class ProofOfCompletion
{
    #[Groups('ProofOfCompletion')]
    public ?string $title = null;

    #[Groups('ProofOfCompletion')]
    public ?string $url = null;

    #[Groups('ProofOfCompletion')]
    public ?Course $theoreticalSubmodule = null;
}