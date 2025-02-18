<?php

namespace App\Entity;

use App\Repository\QuizQuestionChoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: QuizQuestionChoiceRepository::class)]
class QuizQuestionChoice extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'quizQuestionChoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuizQuestion $quizQuestion = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Gedmo\Translatable]
    private ?string $text = null;

    #[ORM\Column]
    private ?bool $correct = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuizQuestion(): ?QuizQuestion
    {
        return $this->quizQuestion;
    }

    public function setQuizQuestion(?QuizQuestion $quizQuestion): self
    {
        $this->quizQuestion = $quizQuestion;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function isCorrect(): ?bool
    {
        if (null === $this->correct) {
            return false;
        }
        return $this->correct;
    }

    public function setCorrect(bool $correct): self
    {
        $this->correct = $correct;

        return $this;
    }
}
