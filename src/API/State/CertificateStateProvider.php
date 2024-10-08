<?php

namespace App\API\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\API\Entity\Certificate;
use App\Entity\Course;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class CertificateStateProvider implements ProviderInterface
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

        $certificates = [];
        $locale = $this->getLocale();

        if (null !== $user->getAllCoursesPassedAt()) {
            $certificate = new Certificate();
            $certificate->title = $this->translator->trans('trophy.allCourses', [], 'app');
            $certificate->url = $this->router->generate('file_fetch_golden_certificate', ['_locale' => $locale], RouterInterface::ABSOLUTE_URL);
            $certificates[] = $certificate;
        }

        $courseRepository = $this->em->getRepository(Course::class);
        /** @var Course $course */
        foreach ($courseRepository->findPassedByUserAndOrderedByPosition($user, $locale) as $course) {
            $certificate = new Certificate();
            $certificate->title = $course->getNameOrPublicName();
            $certificate->theoreticalSubmodule = $course;
            $certificate->url = $this->router->generate('file_fetch_course_certificate', ['course' => $course->getId(), '_locale' => $locale], RouterInterface::ABSOLUTE_URL);
            $certificates[] = $certificate;
        }

        return $certificates;
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