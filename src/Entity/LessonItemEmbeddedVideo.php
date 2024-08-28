<?php

namespace App\Entity;

use App\Repository\LessonItemEmbeddedVideoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonItemEmbeddedVideoRepository::class)]
class LessonItemEmbeddedVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 255)]
    private ?string $videoUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoUrlAlt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(Lesson $lesson): self
    {
        $this->lesson = $lesson;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function getVideoUrlAlt(): ?string
    {
        return $this->videoUrlAlt;
    }

    public function setVideoUrlAlt(?string $videoUrlAlt): self
    {
        $this->videoUrlAlt = $videoUrlAlt;

        return $this;
    }
}
