<?php

namespace App\Controller;

use App\Entity\NewsItem;
use App\Form\NewsItemType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/news', name: 'news_', requirements: ["_locale" => "%locale.supported%"])]
class NewsController extends BaseController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $news = $this->em->getRepository(NewsItem::class)->findAllDescending();
        return $this->render('news/index.html.twig', ['news' => $news]);
    }

    #[Route('/show/{newsItem}', name: 'show')]
    public function show(NewsItem $newsItem): Response
    {
        return $this->render('news/show.html.twig', ['newsItem' => $newsItem]);
    }

    #[Route('/new', name: 'new')]
    #[IsGranted('ROLE_MODERATOR')]
    public function new(Request $request): Response
    {
        $newsItem = new NewsItem();
        $form = $this->createForm(NewsItemType::class, $newsItem);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newsItem->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($newsItem);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.newsItem.new', domain: 'message'));
            return $this->redirectToRoute('news_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), domain: 'message'));
            }
        }

        return $this->render('news/new.html.twig', ['form' => $form->createView(), 'newsItem' => $newsItem]);
    }

    #[Route('/edit/{newsItem}', name: 'edit')]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(NewsItem $newsItem, Request $request): Response
    {
        $form = $this->createForm(NewsItemType::class, $newsItem);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.newsItem.edit', domain: 'message'));
            return $this->redirectToRoute('news_show', ['newsItem' => $newsItem->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), domain: 'message'));
            }
        }

        return $this->render('news/edit.html.twig', ['form' => $form->createView(), 'newsItem' => $newsItem]);
    }

    #[Route('/delete/{newsItem}', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(NewsItem $newsItem, Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($this->isCsrfTokenValid('news.delete', $csrfToken)) {
            $this->em->remove($newsItem);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.newsItem.delete', domain: 'message'));
            return $this->redirectToRoute('news_index');
        }
        return $this->redirectToRoute('news_show', ['newsItem' => $newsItem->getId()]);
    }
}