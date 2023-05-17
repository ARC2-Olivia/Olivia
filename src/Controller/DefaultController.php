<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route("/", name: "index")]
    public function index(): Response
    {
        $package = new Package(new EmptyVersionStrategy());
        $testimonials = [
            ['title' => 'Testimonial #1', 'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'image' => $package->getUrl('build/images/avatar-empty.svg'), 'personName' => 'John Doe', 'personJob' => 'Job name'],
            ['title' => 'Testimonial #2', 'text' => 'Pellentesque ex sem, tristique ac auctor a, viverra ullamcorper tortor.', 'image' => $package->getUrl('build/images/avatar-empty.svg'), 'personName' => 'John Doe', 'personJob' => 'Job name'],
            ['title' => 'Testimonial #3', 'text' => 'Pellentesque placerat a turpis ut vehicula. In hac habitasse platea dictumst. Aenean nibh ante, eleifend vel lacinia in, pharetra quis elit.', 'image' => $package->getUrl('build/images/avatar-empty.svg'), 'personName' => 'Jane Doe', 'personJob' => 'Job name'],
        ];
        return $this->render('default/index.html.twig', ['testimonials' => $testimonials]);
    }

    #[Route("/change-locale/{locale}", name: "change_locale")]
    public function changeLocale(string $locale, Request $request): Response
    {
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');

        $changedLocale = match ($locale) {
            $alternateLocale => $alternateLocale,
            default => $defaultLocale
        };

        $request->getSession()->set('_locale', $changedLocale);
        if ($request->getLocale() === null) $request->setLocale($changedLocale);

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    #[Route("/profile", name: "profile")]
    #[IsGranted("ROLE_USER")]
    public function profile(): Response
    {
        return $this->render('default/profile.html.twig');
    }
}