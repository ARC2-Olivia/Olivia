<?php

namespace App\Controller;

use App\Entity\Topic;
use App\Form\TopicType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/topic", name: "topic_", requirements: ["_locale" => "%locale.supported%"])]
class TopicController extends BaseController
{
    #[Route("/", name: "index")]
    public function index(): Response
    {
        $topics = $this->em->getRepository(Topic::class)->findAll();
        return $this->render('topic/index.html.twig', ['topics' => $topics]);
    }

    #[Route("/new", name: "new")]
    #[IsGranted("ROLE_MODERATOR")]
    public function new(Request $request): Response
    {
        $topic = new Topic();
        $form = $this->createForm(TopicType::class, $topic, ['include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

        }

        return $this->render('topic/new.html.twig', ['form' => $form->createView()]);
    }
}