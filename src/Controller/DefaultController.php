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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController
{
    #[Route("/", name: "index_without_locale")]
    public function indexWithoutLocale(): Response
    {
        return $this->redirectToRoute('index', ['_locale' => $this->getParameter('locale.default')]);
    }

    #[Route("/{_locale}", name: "index", requirements: ["_locale" => "%locale.supported%"])]
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
}