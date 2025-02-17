<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/accessibility', name: 'accessibility_')]
class AccessibilityController extends AbstractController
{
    #[Route('/zoom/update', name: 'zoom_update', methods: ['POST'])]
    public function updateZoom(Request $request): Response
    {
        $zoomLevel = $request->request->get('zoomLevel');
        if (is_numeric($zoomLevel)) {
            $request->getSession()->set('accessibility.zoomLevel', $zoomLevel);
        }
        return $this->json(['success' => true]);
    }
}