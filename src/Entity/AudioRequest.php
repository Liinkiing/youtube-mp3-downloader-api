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
 * @UniqueEntity("youtubeUrl")
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
     * @Groups({"api", "mercure"})
     * @Assert\Url()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $youtubeUrl;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\OneToOne(targetEntity="App\Entity\Audio", mappedBy="request", cascade={"persist", "remove"})
     */
    private $audio;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="boolean")
     */
    private $isPlaylist = false;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $playlistTitle;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private $playlistItemsCount;

    public function isProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(bool $isProcessed): self
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function setYoutubeUrl(string $youtubeUrl): self
    {
        $this->youtubeUrl = $youtubeUrl;

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

    public function getIsPlaylist(): ?bool
    {
        return $this->isPlaylist;
    }

    public function setIsPlaylist(bool $isPlaylist): self
    {
        $this->isPlaylist = $isPlaylist;

        return $this;
    }

    public function getPlaylistTitle(): ?string
    {
        return $this->playlistTitle;
    }

    public function setPlaylistTitle(?string $playlistTitle): self
    {
        $this->playlistTitle = $playlistTitle;

        return $this;
    }

    public function getPlaylistItemsCount(): ?int
    {
        return $this->playlistItemsCount;
    }

    public function setPlaylistItemsCount(?int $playlistItemsCount): self
    {
        $this->playlistItemsCount = $playlistItemsCount;

        return $this;
    }
}
