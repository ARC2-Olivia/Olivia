<?php

namespace App\Controller;

use App\Entity\Note;
use App\Repository\LessonRepository;
use App\Repository\NoteRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/note", name: "note_")]
class NoteController extends AbstractController
{
    #[Route("/update", name: "update", methods: ["PATCH"])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, NoteRepository $noteRepository, LessonRepository $lessonRepository, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent());
        $success = false;

        if ($data !== null && $data !== false) {
            try {
                $lesson = $lessonRepository->find($data->lesson);
                $user = $userRepository->find($data->user);
                if ($lesson !== null && $user !== null && $user->getId() === $this->getUser()->getId()) {
                    $note = $noteRepository->findOneBy(['lesson' => $lesson, 'user' => $user]);
                    if ($note === null) $note = (new Note())->setLesson($lesson)->setUser($user);
                    $note->setText($data->text)->setUpdatedAt(new \DateTimeImmutable());
                    $noteRepository->save($note, true);
                    $success = true;
                }
            } catch (\Exception $ex) {
            }
        }

        return new JsonResponse(['status' => $success]);
    }
}