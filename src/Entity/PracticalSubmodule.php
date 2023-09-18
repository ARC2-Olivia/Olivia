<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PracticalSubmoduleRepository::class)]
class PracticalSubmodule extends TranslatableEntity
{
    public const MODE_OF_OPERATION_SIMPLE = 'simple';
    public const MODE_OF_OPERATION_ADVANCED = 'advanced';

    public const TERMINOLOGY_ASSESSMENT = 'assessment';
    public const TERMINOLOGY_PRIVACY_POLICY = 'privacyPolicy';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "error.practicalSubmodule.name")]
    #[Gedmo\Translatable]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $publicName = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "error.practicalSubmodule.description")]
    #[Gedmo\Translatable]
    private ?string $description = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Gedmo\Translatable]
    private array $tags = [];

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmoduleQuestion::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleQuestions;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmoduleProcessor::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleProcessors;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'practicalSubmodules')]
    private Collection $courses;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmodulePage::class, orphanRemoval: true)]
    private Collection $practicalSubmodulePages;

    #[ORM\Column(nullable: true)]
    private ?bool $paging = null;

    #[ORM\Column(name: 'op_mode', length: 16)]
    #[Assert\Choice(choices: [PracticalSubmodule::MODE_OF_OPERATION_SIMPLE, PracticalSubmodule::MODE_OF_OPERATION_ADVANCED], message: 'error.practicalSubmodule.modeOfOperation')]
    private ?string $modeOfOperation = null;

    #[ORM\Column(nullable: true)]
    private ?bool $processorGroupingEnabled = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmoduleProcessorGroup::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleProcessorGroups;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $reportComment = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Assert\Choice(choices: [PracticalSubmodule::TERMINOLOGY_ASSESSMENT, PracticalSubmodule::TERMINOLOGY_PRIVACY_POLICY], message: 'error.practicalSubmodule.terminology.invalid')]
    private ?string $terminology = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmodules')]
    private ?Topic $topic = null;

    public function __construct()
    {
        $this->practicalSubmoduleQuestions = new ArrayCollection();
        $this->practicalSubmoduleProcessors = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->practicalSubmodulePages = new ArrayCollection();
        $this->practicalSubmoduleProcessorGroups = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmoduleQuestion>
     */
    public function getPracticalSubmoduleQuestions(): Collection
    {
        return $this->practicalSubmoduleQuestions;
    }

    public function addPracticalSubmoduleQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        if (!$this->practicalSubmoduleQuestions->contains($practicalSubmoduleQuestion)) {
            $this->practicalSubmoduleQuestions->add($practicalSubmoduleQuestion);
            $practicalSubmoduleQuestion->setPracticalSubmodule($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        if ($this->practicalSubmoduleQuestions->removeElement($practicalSubmoduleQuestion)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmoduleQuestion->getPracticalSubmodule() === $this) {
                $practicalSubmoduleQuestion->setPracticalSubmodule(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmoduleProcessor>
     */
    public function getPracticalSubmoduleProcessors(): Collection
    {
        return $this->practicalSubmoduleProcessors;
    }

    public function addPracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        if (!$this->practicalSubmoduleProcessors->contains($practicalSubmoduleProcessor)) {
            $this->practicalSubmoduleProcessors->add($practicalSubmoduleProcessor);
            $practicalSubmoduleProcessor->setPracticalSubmodule($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        if ($this->practicalSubmoduleProcessors->removeElement($practicalSubmoduleProcessor)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmoduleProcessor->getPracticalSubmodule() === $this) {
                $practicalSubmoduleProcessor->setPracticalSubmodule(null);
            }
        }

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

    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): self
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
        }

        return $this;
    }

    public function removeCourse(Course $course): self
    {
        $this->courses->removeElement($course);

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmodulePage>
     */
    public function getPracticalSubmodulePages(): Collection
    {
        return $this->practicalSubmodulePages;
    }

    public function addPracticalSubmodulePage(PracticalSubmodulePage $practicalSubmodulePage): self
    {
        if (!$this->practicalSubmodulePages->contains($practicalSubmodulePage)) {
            $this->practicalSubmodulePages->add($practicalSubmodulePage);
            $practicalSubmodulePage->setPracticalSubmodule($this);
        }

        return $this;
    }

    public function removePracticalSubmodulePage(PracticalSubmodulePage $practicalSubmodulePage): self
    {
        if ($this->practicalSubmodulePages->removeElement($practicalSubmodulePage)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmodulePage->getPracticalSubmodule() === $this) {
                $practicalSubmodulePage->setPracticalSubmodule(null);
            }
        }

        return $this;
    }

    public function isPaging(): ?bool
    {
        return $this->paging;
    }

    public function setPaging(?bool $paging): self
    {
        $this->paging = $paging;

        return $this;
    }

    public function getModeOfOperation(): ?string
    {
        return $this->modeOfOperation;
    }

    public function setModeOfOperation(string $modeOfOperation): self
    {
        $this->modeOfOperation = $modeOfOperation;

        return $this;
    }

    public function isSimpleModeOfOperation(): bool
    {
        return self::MODE_OF_OPERATION_SIMPLE === $this->modeOfOperation;
    }

    public function isAdvancedModeOfOperation(): bool
    {
        return self::MODE_OF_OPERATION_ADVANCED === $this->modeOfOperation;
    }

    public function isProcessorGroupingEnabled(): ?bool
    {
        return $this->processorGroupingEnabled;
    }

    public function setProcessorGroupingEnabled(?bool $processorGroupingEnabled): self
    {
        $this->processorGroupingEnabled = $processorGroupingEnabled;

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmoduleProcessorGroup>
     */
    public function getPracticalSubmoduleProcessorGroups(): Collection
    {
        return $this->practicalSubmoduleProcessorGroups;
    }

    public function addPracticalSubmoduleProcessorGroup(PracticalSubmoduleProcessorGroup $practicalSubmoduleProcessorGroup): self
    {
        if (!$this->practicalSubmoduleProcessorGroups->contains($practicalSubmoduleProcessorGroup)) {
            $this->practicalSubmoduleProcessorGroups->add($practicalSubmoduleProcessorGroup);
            $practicalSubmoduleProcessorGroup->setPracticalSubmodule($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleProcessorGroup(PracticalSubmoduleProcessorGroup $practicalSubmoduleProcessorGroup): self
    {
        if ($this->practicalSubmoduleProcessorGroups->removeElement($practicalSubmoduleProcessorGroup)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmoduleProcessorGroup->getPracticalSubmodule() === $this) {
                $practicalSubmoduleProcessorGroup->setPracticalSubmodule(null);
            }
        }

        return $this;
    }

    public function getReportComment(): ?string
    {
        return $this->reportComment;
    }

    public function setReportComment(?string $reportComment): self
    {
        $this->reportComment = $reportComment;

        return $this;
    }

    public function getTerminology(): ?string
    {
        if (null === $this->terminology) {
            return self::TERMINOLOGY_ASSESSMENT;
        }
        return $this->terminology;
    }

    public function setTerminology(?string $terminology): self
    {
        $this->terminology = $terminology;

        return $this;
    }

    public function getTopic(): ?Topic
    {
        return $this->topic;
    }

    public function setTopic(?Topic $topic): self
    {
        $this->topic = $topic;

        return $this;
    }
}
