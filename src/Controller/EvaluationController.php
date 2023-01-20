<?php

namespace App\Controller;

use App\Repository\EvaluationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/evaluation", name: "evaluation_")]
class EvaluationController extends BaseController
{
    #[Route("/", name: "index")]
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        $evaluations = $evaluationRepository->findAll();
        return $this->render("evaluation/index.html.twig", ['evaluations' => $evaluations]);
    }
}