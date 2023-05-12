<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route("/", name: "index")]
    public function index(): Response
    {
        return $this->render('default/index.html.twig');
    }

    #[Route("/change-locale", name: "change_locale")]
    public function changeLocale(Request $request): Response
    {
        $locale = $request->getLocale() ?? $request->getDefaultLocale();
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');
        $changedLocale = $locale === $defaultLocale ? $alternateLocale : $defaultLocale;

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