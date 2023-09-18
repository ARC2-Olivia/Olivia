<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "error.course.name")]
    #[Gedmo\Translatable]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $publicName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "error.course.description")]
    #[Gedmo\Translatable]
    private ?string $description = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $estimatedWorkload = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Gedmo\Translatable]
    private array $tags = [];

    #[ORM\ManyToMany(targetEntity: Instructor::class, inversedBy: 'courses')]
    private Collection $instructors;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Lesson::class, orphanRemoval: true)]
    private Collection $lessons;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Enrollment::class, orphanRemoval: true)]
    private Collection $enrollments;

    #[ORM\ManyToMany(targetEntity: PracticalSubmodule::class, mappedBy: 'courses')]
    private Collection $practicalSubmodules;

    public function __construct()
    {
        $this->instructors = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->practicalSubmodules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPublicName(): ?string
    {
        return $this->publicName;
    }

    public function setPublicName(?string $publicName): self
    {
        $this->publicName = $publicName;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getEstimatedWorkload(): ?string
    {
        return $this->estimatedWorkload;
    }

    public function setEstimatedWorkload(?string $estimatedWorkload): self
    {
        $this->estimatedWorkload = $estimatedWorkload;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return Collection<int, Instructor>
     */
    public function getInstructors(): Collection
    {
        return $this->instructors;
    }

    public function addInstructor(Instructor $instructor): self
    {
        if (!$this->instructors->contains($instructor)) {
            $this->instructors->add($instructor);
        }

        return $this;
    }

    public function removeInstructor(Instructor $instructor): self
    {
        $this->instructors->removeElement($instructor);

        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getOrderedLessons(): Collection
    {
        $iterator = $this->lessons->getIterator();
        $iterator->uasort(function(Lesson $lessonA, Lesson $lessonB) {
            if ($lessonA->getPosition() === $lessonB->getPosition()) return 0;
            return $lessonA->getPosition() < $lessonB->getPosition() ? -1 : 1;
        });
        return new ArrayCollection($iterator->getArrayCopy());
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessonsOfType(string $lessonType): Collection
    {
        return $this->lessons->filter(function (Lesson $l) use ($lessonType) { return $lessonType === $l->getType(); });
    }

    public function addLesson(Lesson $lesson): self
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setCourse($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getCourse() === $this) {
                $lesson->setCourse(null);
            }
        }

        return $this;
    }

    public function countNonQuizLessons(): int
    {
        $count = 0;
        foreach ($this->getLessons() as $lesson) {
            if ($lesson::TYPE_QUIZ !== $lesson->getType()) {
                $count++;
            }
        }
        return $count;
    }

    public function countQuizLessons(): int
    {
        $count = 0;
        foreach ($this->getLessons() as $lesson) {
            if ($lesson::TYPE_QUIZ === $lesson->getType()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): self
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setCourse($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): self
    {
        if ($this->enrollments->removeElement($enrollment)) {
            // set the owning side to null (unless already changed)
            if ($enrollment->getCourse() === $this) {
                $enrollment->setCourse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmodule>
     */
    public function getPracticalSubmodules(): Collection
    {
        return $this->practicalSubmodules;
    }

    public function addPracticalSubmodule(PracticalSubmodule $practicalSubmodule): self
    {
        if (!$this->practicalSubmodules->contains($practicalSubmodule)) {
            $this->practicalSubmodules->add($practicalSubmodule);
            $practicalSubmodule->addCourse($this);
        }

        return $this;
    }

    public function removePracticalSubmodule(PracticalSubmodule $practicalSubmodule): self
    {
        if ($this->practicalSubmodules->removeElement($practicalSubmodule)) {
            $practicalSubmodule->removeCourse($this);
        }

        return $this;
    }
}
