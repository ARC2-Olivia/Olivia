<?php

namespace App\Twig\Runtime;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class VideoRuntime implements RuntimeExtensionInterface
{
    private ?RequestStack $requestStack = null;
    private ?ParameterBagInterface $parameterBag = null;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
    }

    public function isTopicIndexVideoUrlAvailable(): bool
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        if ($locale === $this->parameterBag->get('locale.alternate')) {
            return '' !== $this->parameterBag->get('video.topic.index.alternate');
        }
        return '' !== $this->parameterBag->get('video.topic.index.default');
    }

    public function getTopicIndexVideoUrl(): ?string
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        if ($locale === $this->parameterBag->get('locale.alternate')) {
            return $this->parameterBag->get('video.topic.index.alternate');
        }
        return $this->parameterBag->get('video.topic.index.default');
    }
}