<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PracticalSubmoduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PracticalSubmoduleRepository::class)]
#[ApiResource(normalizationContext: ['groups' => ['PracticalSubmodule']])]
#[Get]
#[GetCollection]
class PracticalSubmodule extends TranslatableEntity
{
    public const
        MODE_OF_OPERATION_SIMPLE = 'simple',
        MODE_OF_OPERATION_ADVANCED = 'advanced';

    public const
        EXPORT_TYPE_NONE = 'none',
        EXPORT_TYPE_LIA = 'lia',
        EXPORT_TYPE_SCC = 'scc',
        EXPORT_TYPE_TIA = 'tia',
        EXPORT_TYPE_DPIA = 'dpia',
        EXPORT_TYPE_SIMPLE = 'simple',
        EXPORT_TYPE_COOKIE_BANNER = 'cookieBanner',
        EXPORT_TYPE_COOKIE_POLICY = 'cookiePolicy',
        EXPORT_TYPE_PRIVACY_POLICY = 'privacyPolicy',
        EXPORT_TYPE_RULEBOOK_ON_PDP = 'ruleBookOnPDP',
        EXPORT_TYPE_RULEBOOK_ON_ISS = 'RulebookOnISS',
        EXPORT_TYPE_RESPONDENTS_RIGHTS = 'respondentsRights',
        EXPORT_TYPE_VIDEO_SURVEILLANCE_RULEBOOK = 'videoSurveillanceRulebook',
        EXPORT_TYPE_CONTROLLER_PROCESSOR_CONTRACT = 'controllerProcessorContract',
        EXPORT_TYPE_VIDEO_SURVEILLANCE_NOTIFICATION = 'videoSurveillanceNotification',
        EXPORT_TYPE_PERSONAL_DATA_PROCESSING_CONSENT = 'consentPersonalDataProcessing',
        EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DC = 'recordsOfProcessingActivitiesDC',
        EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DP = 'recordsOfProcessingActivitiesDP'
    ;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('PracticalSubmodule')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "error.practicalSubmodule.name")]
    #[Gedmo\Translatable]
    #[Groups('PracticalSubmodule')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    #[Groups('PracticalSubmodule')]
    private ?string $publicName = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "error.practicalSubmodule.description")]
    #[Gedmo\Translatable]
    #[Groups('PracticalSubmodule')]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmoduleQuestion::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleQuestions;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmoduleProcessor::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleProcessors;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'practicalSubmodules')]
    #[Groups('PracticalSubmodule')]
    #[SerializedName('linkedTheoreticalSubmodules')]
    private Collection $courses;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmodulePage::class, orphanRemoval: true)]
    private Collection $practicalSubmodulePages;

    #[ORM\Column(nullable: true)]
    private ?bool $paging = null;

    #[ORM\Column(name: 'op_mode', length: 16)]
    #[Assert\Choice(choices: [PracticalSubmodule::MODE_OF_OPERATION_SIMPLE, PracticalSubmodule::MODE_OF_OPERATION_ADVANCED], message: 'error.practicalSubmodule.modeOfOperation')]
    private ?string $modeOfOperation = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodule', targetEntity: PracticalSubmoduleProcessorGroup::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleProcessorGroups;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $reportComment = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmodules')]
    #[Groups('PracticalSubmodule')]
    private ?Topic $topic = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups('PracticalSubmodule')]
    private ?int $position = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $exportType = null;

    #[ORM\Column(nullable: true)]
    #[Groups('PracticalSubmodule')]
    private ?bool $revisionMode = null;

    #[ORM\Column(nullable: true)]
    private ?bool $answersReportHidden = null;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->practicalSubmodulePages = new ArrayCollection();
        $this->practicalSubmoduleQuestions = new ArrayCollection();
        $this->practicalSubmoduleProcessors = new ArrayCollection();
        $this->practicalSubmoduleProcessorGroups = new ArrayCollection();
    }

    public static function getTaggableExportTypes(): array
    {
        return [
            self::EXPORT_TYPE_LIA,
            self::EXPORT_TYPE_SCC,
            self::EXPORT_TYPE_TIA,
            self::EXPORT_TYPE_DPIA,
            self::EXPORT_TYPE_COOKIE_BANNER,
            self::EXPORT_TYPE_COOKIE_POLICY,
            self::EXPORT_TYPE_PRIVACY_POLICY,
            self::EXPORT_TYPE_RULEBOOK_ON_ISS,
            self::EXPORT_TYPE_RULEBOOK_ON_PDP,
            self::EXPORT_TYPE_RESPONDENTS_RIGHTS,
            self::EXPORT_TYPE_VIDEO_SURVEILLANCE_RULEBOOK,
            self::EXPORT_TYPE_CONTROLLER_PROCESSOR_CONTRACT,
            self::EXPORT_TYPE_VIDEO_SURVEILLANCE_NOTIFICATION,
            self::EXPORT_TYPE_PERSONAL_DATA_PROCESSING_CONSENT,
            self::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DC,
            self::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DP,
        ];
    }

    public static function exportsToWord(PracticalSubmodule $practicalSubmodule): bool
    {
        return in_array($practicalSubmodule->getExportType(), [
            self::EXPORT_TYPE_LIA,
            self::EXPORT_TYPE_SCC,
            self::EXPORT_TYPE_TIA,
            self::EXPORT_TYPE_DPIA,
            self::EXPORT_TYPE_COOKIE_POLICY,
            self::EXPORT_TYPE_PRIVACY_POLICY,
            self::EXPORT_TYPE_RULEBOOK_ON_ISS,
            self::EXPORT_TYPE_RULEBOOK_ON_PDP,
            self::EXPORT_TYPE_RESPONDENTS_RIGHTS,
            self::EXPORT_TYPE_VIDEO_SURVEILLANCE_RULEBOOK,
            self::EXPORT_TYPE_CONTROLLER_PROCESSOR_CONTRACT,
            self::EXPORT_TYPE_PERSONAL_DATA_PROCESSING_CONSENT,
        ]);
    }

    public static function exportsToExcel(PracticalSubmodule $practicalSubmodule): bool
    {
        return in_array($practicalSubmodule->getExportType(), [self::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DC, self::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DP]);
    }

    public static function exportsToPdf(PracticalSubmodule $practicalSubmodule): bool
    {
        return in_array($practicalSubmodule->getExportType(), [self::EXPORT_TYPE_VIDEO_SURVEILLANCE_NOTIFICATION]);
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

    public function getNameOrPublicName(): ?string
    {
        return null !== $this->publicName ? $this->publicName : $this->name;
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

    public function canRunAssessment(): bool
    {
        return true === $this->paging ? !$this->practicalSubmodulePages->isEmpty() : !$this->practicalSubmoduleQuestions->isEmpty();
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

    public function getTopic(): ?Topic
    {
        return $this->topic;
    }

    public function setTopic(?Topic $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getExportType(): ?string
    {
        if (null === $this->exportType) {
            return self::EXPORT_TYPE_NONE;
        }
        return $this->exportType;
    }

    public function setExportType(?string $exportType): self
    {
        $this->exportType = $exportType;

        return $this;
    }

    public function isRevisionMode(): ?bool
    {
        if (null === $this->revisionMode) {
            return false;
        }
        return $this->revisionMode;
    }

    public function setRevisionMode(?bool $revisionMode): self
    {
        $this->revisionMode = $revisionMode;

        return $this;
    }

    public function isAnswersReportHidden(): ?bool
    {
        if (null === $this->answersReportHidden) {
            return false;
        }
        return $this->answersReportHidden;
    }

    public function setAnswersReportHidden(?bool $answersReportHidden): self
    {
        $this->answersReportHidden = $answersReportHidden;

        return $this;
    }
}
