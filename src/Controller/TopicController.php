<?php

namespace App\Controller;

use App\Entity\Topic;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/topic", name: "topic_")]
class TopicController extends BaseController
{
    #[Route("/", name: "index")]
    public function index(): Response
    {
        $topics = $this->em->getRepository(Topic::class)->findAll();
        return $this->render('topic/index.html.twig', ['topics' => $topics]);
    }
}