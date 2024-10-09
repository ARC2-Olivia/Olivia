<?php

namespace App\API\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\API\State\PracticalSubmoduleAnswersInfoProvider;
use App\Entity\PracticalSubmodule;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/practical_submodules/{id}/answers.{_format}',
            uriVariables: ['id']
        ),
        new GetCollection(uriTemplate: '/practical_submodules/answers.{_format}')
    ],
    normalizationContext: ['groups' => ['api']],
    provider: PracticalSubmoduleAnswersInfoProvider::class
)]
class PracticalSubmoduleAnswersInfo
{
    #[Groups('api')]
    public ?int $id = null;

    #[Groups('api')]
    public ?PracticalSubmodule $practicalSubmodule = null;

    #[Groups('api')]
    public array $answers = [];
}