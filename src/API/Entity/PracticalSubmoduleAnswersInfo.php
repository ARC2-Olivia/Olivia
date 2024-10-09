<?php

namespace App\API\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\API\State\PracticalSubmoduleAnswersInfoProvider;
use App\Entity\PracticalSubmodule;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/practical_submodules/{id}/answers.{_format}',
            uriVariables: ['id']
        ),
        new GetCollection(uriTemplate: '/practical_submodules/answers.{_format}')
    ],
    normalizationContext: ['groups' => ['PracticalSubmoduleAnswers']],
    provider: PracticalSubmoduleAnswersInfoProvider::class
)]
class PracticalSubmoduleAnswersInfo
{
    #[Groups('PracticalSubmoduleAnswers')]
    public ?int $id = null;

    #[Groups('PracticalSubmoduleAnswers')]
    #[MaxDepth(1)]
    public ?PracticalSubmodule $practicalSubmodule = null;

    #[Groups('PracticalSubmoduleAnswers')]
    public array $answers = [];
}