<?php

namespace App\Service;

use App\Entity\DataRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
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

        $csvFiles = [
            'User.csv' => $this->makeUserCsv($dataRequest, $fs),
            'Note.csv' => $this->makeNoteCsv($dataRequest, $fs),
            'TermsOfService.csv' => $this->makeTermsOfServiceCsv($dataRequest, $fs),
        ];

        $zip = new \ZipArchive();
        $zipFilename = $fs->tempnam($this->parameterBag->get('dir.temp'), 'Olivia-user-data-', '.zip');;
        $zipFileOpened = $zip->open($zipFilename, \ZipArchive::CREATE);

        if ($zipFileOpened === true) {
            foreach ($csvFiles as $name => $filepath) {
                $zip->addFile($filepath, $name);
            }
            $zip->close();

            $email = (new Email())
                ->from($this->parameterBag->get('mail.from'))
                ->to($dataRequest->getUser()->getEmail())
                ->subject($this->translator->trans('mail.dataAccess.subject', [], 'mail'))
                ->text($this->translator->trans('mail.dataAccess.body', [], 'mail'))
                ->attachFromPath($zipFilename, 'Olivia-user-data.zip', 'application/zip')
            ;
            $this->mailer->send($email);

            $dataRequest->setResolvedAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        unlink($zipFilename);
        foreach ($csvFiles as $name => $filepath) {
            unlink($filepath);
        }
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

    private function makeUserCsv(DataRequest $dataRequest, Filesystem $fs): string
    {
        $csvUser = $fs->tempnam($this->parameterBag->get('dir.temp'), 'User-', '.csv');
        if (($stream = fopen($csvUser, 'w')) !== false) {
            $header = [
                $this->translator->trans('user.dataAccess.id', [], 'app'),
                $this->translator->trans('user.dataAccess.email', [], 'app'),
                $this->translator->trans('user.dataAccess.roles', [], 'app')
            ];
            fputcsv($stream, $header, ';');

            $row = [
                strval($dataRequest->getUser()->getId()),
                $dataRequest->getUser()->getEmail(),
                implode(',', $dataRequest->getUser()->getRoles())
            ];
            fputcsv($stream, $row, ';');

            fclose($stream);
        }
        return $csvUser;
    }

    private function makeNoteCsv(DataRequest $dataRequest, Filesystem $fs)
    {
        $csvNote = $fs->tempnam($this->parameterBag->get('dir.temp'), 'Note-', '.csv');
        if (($stream = fopen($csvNote, 'w')) !== false) {
            $header = [
                $this->translator->trans('note.dataAccess.id', [], 'app'),
                $this->translator->trans('note.dataAccess.lesson', [], 'app'),
                $this->translator->trans('note.dataAccess.text', [], 'app'),
                $this->translator->trans('note.dataAccess.updatedAt', [], 'app'),
            ];
            fputcsv($stream, $header, ';');

            $dumpedNotes = $this->em->getRepository(\App\Entity\Note::class)->dumpForDataAccess($dataRequest->getUser());
            foreach ($dumpedNotes as $item) {
                $row = [$item['note_id'], $item['lesson_id'], $item['text'], $item['updated_at']];
                fputcsv($stream, $row, ';');
            }

            fclose($stream);
        }

        return $csvNote;
    }

    private function makeTermsOfServiceCsv(DataRequest $dataRequest, Filesystem $fs)
    {
        $csvAcceptedTermsOfService = $fs->tempnam($this->parameterBag->get('dir.temp'), 'AcceptedTermsOfService-', '.csv');
        if (($stream = fopen($csvAcceptedTermsOfService, 'w')) !== false) {
            $header = [
                $this->translator->trans('termsOfService.dataAccess.id', [], 'app'),
                $this->translator->trans('termsOfService.dataAccess.version', [], 'app'),
                $this->translator->trans('termsOfService.dataAccess.revision', [], 'app'),
                $this->translator->trans('termsOfService.dataAccess.startedAt', [], 'app'),
                $this->translator->trans('termsOfService.dataAccess.endedAt', [], 'app'),
                $this->translator->trans('termsOfService.dataAccess.content', [], 'app'),
                $this->translator->trans('termsOfService.dataAccess.active', [], 'app'),
                $this->translator->trans('termsOfService.dataAccess.acceptedAt', [], 'app'),
            ];
            fputcsv($stream, $header, ';');

            $dumpedTermsOfServices = $this->em->getRepository(\App\Entity\Gdpr::class)->dumpForDataAccess($dataRequest->getUser());
            foreach ($dumpedTermsOfServices as $item) {
                $row = [$item['id'], $item['version'], $item['revision'], $item['started_at'], $item['ended_at'], $item['content'], $item['active'], $item['accepted_at']];
                fputcsv($stream, $row, ';');
            }
            fclose($stream);
        }
        return $csvAcceptedTermsOfService;
    }
}