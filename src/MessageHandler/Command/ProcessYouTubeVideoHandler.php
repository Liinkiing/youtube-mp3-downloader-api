<?php


namespace App\MessageHandler\Command;


use App\Entity\Audio;
use App\Message\Command\ProcessYouTubeVideo;
use App\Repository\AudioRequestRepository;
use App\Wrapper\Ytomp3Wrapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class ProcessYouTubeVideoHandler implements MessageHandlerInterface
{
    private $repository;
    private $em;
    private $ytomp3;

    public function __construct(
        AudioRequestRepository $repository,
        EntityManagerInterface $em,
        Ytomp3Wrapper $ytomp3
    ) {
        $this->repository = $repository;
        $this->em = $em;
        $this->ytomp3 = $ytomp3;
    }

    public function __invoke(ProcessYouTubeVideo $message)
    {
        $request = $this->repository->find($message->getRequestId());

        if ($request && !$request->isProcessed()) {
            $informations = $this->ytomp3->process($request->getYoutubeUrl());
            $audio = new Audio();
            $audio
                ->setTitle($informations['title'])
                ->setArtist($informations['artist'])
                ->setDisplayName($informations['displayName'])
                ->setFilename($informations['filename'])
                ->setMimeType($informations['mimeType']);

            $request
                ->setAudio($audio)
                ->setIsProcessed(true);

            $this->em->flush();
        } else {
            throw new NotFoundResourceException(
                sprintf('Could not find AudioRequest with ID "%s"', $message->getRequestId())
            );
        }
    }
}
