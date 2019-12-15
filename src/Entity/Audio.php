<?php

namespace App\Entity;

use App\Traits\Timestampable;
use App\Traits\UuidTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AudioRepository")
 */
class Audio
{
    use UuidTrait;
    use Timestampable;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="string", length=255)
     */
    private $mimeType;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AudioRequest", inversedBy="audio", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $request;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $artist;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $displayName;

    /**
     * @Groups({"api", "mercure"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thumbnailUrl;

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getRequest(): ?AudioRequest
    {
        return $this->request;
    }

    public function setRequest(AudioRequest $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(?string $artist): self
    {
        $this->artist = $artist;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }
}
