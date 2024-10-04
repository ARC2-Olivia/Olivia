<?php

namespace App\API\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class CurrentUserFilter implements FilterInterface
{
    private ?Security $security = null;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [])
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (null === $user) {
            throw new AccessDeniedException();
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $where = sprintf('%s.user = :user', $alias);
        $queryBuilder->andWhere($where)->setParameter('user', $user);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'enrollment' => [
                'property' => 'user',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'Filter entities that are related to the current user.'],
                'openapi' => ['description' => 'Filter entities that are related to the current user.']
            ]
        ];
    }
}