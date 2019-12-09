<?php

namespace App\Entity;

use App\Traits\Timestampable;
use App\Traits\UuidTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AudioRequestRepository")
 * @UniqueEntity("youtubeId")
 */
class AudioRequest
{
    use Timestampable;
    use UuidTrait;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isProcessed = false;

    /**
     * @Groups({"api"})
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $youtubeId;

    /**
     * @Groups({"api"})
     * @ORM\OneToOne(targetEntity="App\Entity\Audio", mappedBy="request", cascade={"persist", "remove"})
     */
    private $audio;

    public function isProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(bool $isProcessed): self
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    public function getYoutubeId(): ?string
    {
        return $this->youtubeId;
    }

    public function setYoutubeId(string $youtubeId): self
    {
        $this->youtubeId = $youtubeId;

        return $this;
    }

    public function getAudio(): ?Audio
    {
        return $this->audio;
    }

    public function setAudio(Audio $audio): self
    {
        $this->audio = $audio;

        // set the owning side of the relation if necessary
        if ($audio->getRequest() !== $this) {
            $audio->setRequest($this);
        }

        return $this;
    }
}
