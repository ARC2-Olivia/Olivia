<?php

namespace App\API\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\API\Entity\ProofOfCompletion;
use App\Entity\Course;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProofOfCompletionStateProvider implements ProviderInterface
{
    private ?Security $security = null;
    private ?EntityManagerInterface $em = null;
    private ?TranslatorInterface $translator = null;
    private ?RouterInterface $router = null;
    private ?RequestStack $requestStack = null;
    private ?ParameterBagInterface $parameterBag = null;

    public function __construct(
        Security $security,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        RouterInterface $router,
        RequestStack $requestStack,
        ParameterBagInterface $parameterBag)
    {
        $this->security = $security;
        $this->em = $em;
        $this->translator = $translator;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @inheritDoc
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (null === $user) {
            return [];
        }

        $proofs = [];
        $locale = $this->getLocale();

        if (null !== $user->getAllCoursesPassedAt()) {
            $proof = new ProofOfCompletion();
            $proof->title = $this->translator->trans('trophy.allCourses', [], 'app', $locale);
            $proof->url = $this->router->generate('file_fetch_golden_certificate', ['_locale' => $locale], RouterInterface::ABSOLUTE_URL);
            $proofs[] = $proof;
        }

        $courseRepository = $this->em->getRepository(Course::class);
        /** @var Course $course */
        foreach ($courseRepository->findPassedByUserAndOrderedByPosition($user, $locale) as $course) {
            $proof = new ProofOfCompletion();
            $proof->title = $course->getNameOrPublicName();
            $proof->theoreticalSubmodule = $course;
            $proof->url = $this->router->generate('file_fetch_course_certificate', ['course' => $course->getId(), '_locale' => $locale], RouterInterface::ABSOLUTE_URL);
            $proofs[] = $proof;
        }

        return $proofs;
    }

    private function getLocale()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request->headers->has('Accept-Language')) {
            return $this->parameterBag->get('locale.default');
        }

        $locale = $request->headers->get('Accept-Language');
        if ($locale !== $this->parameterBag->get('locale.default') && $locale !== $this->parameterBag->get('locale.alternate')) {
            return $this->parameterBag->get('locale.default');
        }

        return $locale;
    }
}