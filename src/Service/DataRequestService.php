<?php

namespace App\Service;

use App\Entity\DataRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataRequestService
{
    private EntityManagerInterface $em;
    private ParameterBagInterface $parameterBag;
    private TranslatorInterface $translator;
    private MailerInterface $mailer;

    private const BATCH_SIZE = 50;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag, TranslatorInterface $translator, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->mailer = $mailer;
    }

    public function resolve(DataRequest $dataRequest): void
    {

        if ($dataRequest->getType() === DataRequest::TYPE_ACCESS) {
            $this->resolveDataAccessRequest($dataRequest);
        } else if ($dataRequest->getType() === DataRequest::TYPE_DELETE) {
            $this->resolveDataDeletionRequest($dataRequest);
        }
    }

    private function resolveDataAccessRequest(DataRequest $dataRequest): void
    {
        $fs = new Filesystem();

        if (!$fs->exists($this->parameterBag->get('dir.temp'))) {
            $fs->mkdir($this->parameterBag->get('dir.temp'));
        }

        $excelFile = Path::join($this->parameterBag->get('dir.temp'), uniqid("{$dataRequest->getUser()->getId()}-", true).'.xlsx');
        $spreadsheet = new Spreadsheet();

        $this->addUserToExcel($dataRequest, $spreadsheet);
        $this->addNotesToExcel($dataRequest, $spreadsheet);
        $this->addGdprToExcel($dataRequest, $spreadsheet);
        $this->addEnrollmentsToExcel($dataRequest, $spreadsheet);
        $this->addLessonCompletionsToExcel($dataRequest, $spreadsheet);
        $this->addQuizQuestionAnswersToExcel($dataRequest, $spreadsheet);
        $this->addPracticalSubmoduleAssessmentsToExcel($dataRequest, $spreadsheet);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($excelFile);

        $email = (new Email())
            ->from($this->parameterBag->get('mail.from'))
            ->to($dataRequest->getUser()->getEmail())
            ->subject($this->translator->trans('mail.dataAccess.subject', [], 'mail'))
            ->text($this->translator->trans('mail.dataAccess.body', [], 'mail'))
            ->attachFromPath($excelFile, 'Olivia-user-data.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ;
        $this->mailer->send($email);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        unlink($excelFile);
    }

    private function resolveDataDeletionRequest(DataRequest $dataRequest): void
    {
        $user = $dataRequest->getUser();
        $identifier = $user->getNameOrEmail();
        $email = $user->getEmail();

        $batchCounter = 0;
        $this->deleteEntitiesForUser($user, \App\Entity\AcceptedGdpr::class, $batchCounter);
        $this->deleteEntitiesForUser($user, \App\Entity\EduLog::class, $batchCounter);
        $this->deleteEntitiesForUser($user, \App\Entity\Enrollment::class, $batchCounter);
        $this->deleteEntitiesForUser($user, \App\Entity\LessonCompletion::class, $batchCounter);
        $this->deleteEntitiesForUser($user, \App\Entity\Note::class, $batchCounter);
        $this->deleteEntitiesForUser($user, \App\Entity\QuizQuestionAnswer::class, $batchCounter);
        $this->deleteEntitiesForUser($user, \App\Entity\PracticalSubmoduleAssessment::class, $batchCounter);

        foreach ($this->em->getRepository(\App\Entity\DataRequest::class)->findUnresolvedByTypeForUser(\App\Entity\DataRequest::TYPE_ACCESS, $user) as $unresolvedDataRequest) {
            $this->deleteEntity($unresolvedDataRequest, $batchCounter);
        }

        foreach ($this->em->getRepository(\App\Entity\DataRequest::class)->findUnresolvedByTypeForUser(\App\Entity\DataRequest::TYPE_DELETE, $user) as $unresolvedDataRequest) {
            if ($dataRequest->getId() !== $unresolvedDataRequest->getId()) {
                $this->deleteEntity($unresolvedDataRequest, $batchCounter);
            }
        }

        foreach ($this->em->getRepository(\App\Entity\DataRequest::class)->findResolvedByTypeForUser(\App\Entity\DataRequest::TYPE_ACCESS, $user) as $resolvedDataRequest) {
            $resolvedDataRequest->setDeletedUserEmail($email)->setUser(null);
        }

        $dataRequest->setResolvedAt(new \DateTimeImmutable())->setDeletedUserEmail($email)->setUser(null);
        $this->em->remove($user);
        $this->em->flush();

        $email = (new Email())
            ->from($this->parameterBag->get('mail.from'))
            ->to($email)
            ->subject($this->translator->trans('mail.dataDeletion.subject', [], 'mail'))
            ->html($this->translator->trans('mail.dataDeletion.body', ['%user%' => $identifier], 'mail'))
        ;
        $this->mailer->send($email);
    }

    private function deleteEntitiesForUser(User $user, string $entityClass, int &$batchCounter): void
    {
        if (!class_exists($entityClass)) {
            return;
        }
        foreach ($this->em->getRepository($entityClass)->findBy(['user' => $user]) as $entity) {
            $this->deleteEntity($entity, $batchCounter);
        }
    }

    private function deleteEntity(mixed $entity, int &$batchCounter): void
    {
        $this->em->remove($entity);
        $batchCounter++;
        if ($batchCounter > self::BATCH_SIZE) {
            $this->em->flush();
            $batchCounter = 0;
        }
    }

    private function addUserToExcel(DataRequest $dataRequest, Spreadsheet $spreadsheet): void
    {
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('User');

        $this->addHeader($worksheet, [
            $this->translator->trans('user.dataAccess.id', [], 'app'),
            $this->translator->trans('user.dataAccess.email', [], 'app'),
            $this->translator->trans('user.dataAccess.roles', [], 'app')
        ]);

        $worksheet->setCellValue('A2', strval($dataRequest->getUser()->getId()));
        $worksheet->setCellValue('B2', strval($dataRequest->getUser()->getEmail()));
        $worksheet->setCellValue('C2', implode(',', $dataRequest->getUser()->getRoles()));
    }

    private function addNotesToExcel(DataRequest $dataRequest, Spreadsheet $spreadsheet): void
    {
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('Notes');

        $this->addHeader($worksheet, [
            $this->translator->trans('note.dataAccess.id', [], 'app'),
            $this->translator->trans('note.dataAccess.lesson', [], 'app'),
            $this->translator->trans('note.dataAccess.text', [], 'app'),
            $this->translator->trans('note.dataAccess.updatedAt', [], 'app')
        ]);

        $dumpedNotes = $this->em->getRepository(\App\Entity\Note::class)->dumpForDataAccess($dataRequest->getUser());
        $rowOffset = 0;
        foreach ($dumpedNotes as $data) {
            $cellAddress = (new CellAddress('A2', $worksheet))->nextRow($rowOffset);
            $worksheet->setCellValue($cellAddress, $data['note_id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['lesson_id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['text']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['updated_at']);
            $rowOffset++;
        }
    }

    private function addGdprToExcel(DataRequest $dataRequest, Spreadsheet $spreadsheet): void
    {
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('Accepted Terms of service');

        $this->addHeader($worksheet, [
            $this->translator->trans('termsOfService.dataAccess.id', [], 'app'),
            $this->translator->trans('termsOfService.dataAccess.version', [], 'app'),
            $this->translator->trans('termsOfService.dataAccess.revision', [], 'app'),
            $this->translator->trans('termsOfService.dataAccess.startedAt', [], 'app'),
            $this->translator->trans('termsOfService.dataAccess.endedAt', [], 'app'),
            $this->translator->trans('termsOfService.dataAccess.active', [], 'app'),
            $this->translator->trans('termsOfService.dataAccess.acceptedAt', [], 'app')
        ]);

        $dumpedGdprs = $this->em->getRepository(\App\Entity\Gdpr::class)->dumpForDataAccess($dataRequest->getUser());
        $rowOffset = 0;
        foreach ($dumpedGdprs as $data) {
            $cellAddress = (new CellAddress('A2', $worksheet))->nextRow($rowOffset);
            $worksheet->setCellValue($cellAddress, $data['id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['version']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['revision']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['started_at']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['ended_at']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['active']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['accepted_at']);
            $rowOffset++;
        }
    }

    private function addEnrollmentsToExcel(DataRequest $dataRequest, Spreadsheet $spreadsheet): void
    {
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('Enrollments');

        $this->addHeader($worksheet, [
            $this->translator->trans('enrollment.dataAccess.id', [], 'app'),
            $this->translator->trans('enrollment.dataAccess.courseId', [], 'app'),
            $this->translator->trans('enrollment.dataAccess.courseName', [], 'app'),
            $this->translator->trans('enrollment.dataAccess.enrolledAt', [], 'app'),
            $this->translator->trans('enrollment.dataAccess.passed', [], 'app')
        ]);

        $dumpedEnrollments = $this->em->getRepository(\App\Entity\Enrollment::class)->dumpForDataAccess($dataRequest->getUser());
        $rowOffset = 0;
        foreach ($dumpedEnrollments as $data) {
            $cellAddress = (new CellAddress('A2', $worksheet))->nextRow($rowOffset);
            $worksheet->setCellValue($cellAddress, $data['id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['course_id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['course_name']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['enrolled_at']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['passed']);
            $rowOffset++;
        }
    }

    private function addLessonCompletionsToExcel(DataRequest $dataRequest, Spreadsheet $spreadsheet): void
    {
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('Lesson completion');

        $this->addHeader($worksheet, [
            $this->translator->trans('lessonCompletion.dataAccess.id', [], 'app'),
            $this->translator->trans('lessonCompletion.dataAccess.lessonId', [], 'app'),
            $this->translator->trans('lessonCompletion.dataAccess.lessonName', [], 'app'),
            $this->translator->trans('lessonCompletion.dataAccess.completed', [], 'app'),
        ]);

        $dumpedLessonCompletions = $this->em->getRepository(\App\Entity\LessonCompletion::class)->dumpForDataAccess($dataRequest->getUser());
        $rowOffset = 0;
        foreach ($dumpedLessonCompletions as $data) {
            $cellAddress = (new CellAddress('A2', $worksheet))->nextRow($rowOffset);
            $worksheet->setCellValue($cellAddress, $data['id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['lesson_id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['lesson_name']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['completed']);
            $rowOffset++;
        }
    }

    private function addQuizQuestionAnswersToExcel(DataRequest $dataRequest, Spreadsheet $spreadsheet): void
    {
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('Quiz question answer');

        $this->addHeader($worksheet, [
            $this->translator->trans('quizQuestionAnswer.dataAcess.id', [], 'app'),
            $this->translator->trans('quizQuestionAnswer.dataAcess.quizQuestionId', [], 'app'),
            $this->translator->trans('quizQuestionAnswer.dataAcess.quizQuestionText', [], 'app'),
            $this->translator->trans('quizQuestionAnswer.dataAcess.answer', [], 'app')
        ]);

        $dumpedLessonCompletions = $this->em->getRepository(\App\Entity\QuizQuestionAnswer::class)->dumpForDataAccess($dataRequest->getUser());
        $rowOffset = 0;
        foreach ($dumpedLessonCompletions as $data) {
            $cellAddress = (new CellAddress('A2', $worksheet))->nextRow($rowOffset);
            $worksheet->setCellValue($cellAddress, $data['id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['question_id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['question_text']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['answer']);
            $rowOffset++;
        }
    }

    private function addPracticalSubmoduleAssessmentsToExcel(DataRequest $dataRequest, Spreadsheet $spreadsheet): void
    {
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('Practical module assessment');

        $this->addHeader($worksheet, [
            $this->translator->trans('practicalSubmoduleAssessment.dataAccess.id', [], 'app'),
            $this->translator->trans('practicalSubmoduleAssessment.dataAccess.practicalSubmoduleId', [], 'app'),
            $this->translator->trans('practicalSubmoduleAssessment.dataAccess.practicalSubmoduleName', [], 'app'),
            $this->translator->trans('practicalSubmoduleAssessment.dataAccess.takenAt', [], 'app'),
            $this->translator->trans('practicalSubmoduleAssessment.dataAccess.lastSubmittedAt', [], 'app'),
            $this->translator->trans('practicalSubmoduleAssessment.dataAccess.completed', [], 'app')
        ]);

        $dumpedLessonCompletions = $this->em->getRepository(\App\Entity\PracticalSubmoduleAssessment::class)->dumpForDataAccess($dataRequest->getUser());
        $rowOffset = 0;
        foreach ($dumpedLessonCompletions as $data) {
            $cellAddress = (new CellAddress('A2', $worksheet))->nextRow($rowOffset);
            $worksheet->setCellValue($cellAddress, $data['id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['practical_submodule_id']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['practical_submodule_name']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['taken_at']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['last_submitted_at']);
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $data['completed']);
            $rowOffset++;
        }
    }

    private function addHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, array $header): void
    {
        $cellAddress = new CellAddress('A1', $worksheet);
        foreach ($header as $item) {
            $cell = $worksheet->getCell($cellAddress);
            $cell->setValue($item);
            $cellAddress = $cellAddress->nextColumn();
        }
    }
}