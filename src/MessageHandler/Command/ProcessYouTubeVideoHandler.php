<?php


namespace App\MessageHandler\Command;


use App\Entity\Audio;
use App\Message\Command\ProcessYouTubeVideo;
use App\Repository\AudioRequestRepository;
use App\Wrapper\Ytomp3Wrapper;
use Doctrine\ORM\EntityManagerInterface;
use Spatie\Regex\MatchResult;
use Spatie\Regex\Regex;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class ProcessYouTubeVideoHandler implements MessageHandlerInterface
{
    private $repository;
    private $em;
    private $ytomp3;
    private $bus;
    private $serializer;

    public function __construct(
        AudioRequestRepository $repository,
        EntityManagerInterface $em,
        MessageBusInterface $bus,
        Ytomp3Wrapper $ytomp3,
        SerializerInterface $serializer
    ) {
        $this->repository = $repository;
        $this->em = $em;
        $this->ytomp3 = $ytomp3;
        $this->bus = $bus;
        $this->serializer = $serializer;
    }

    public function __invoke(ProcessYouTubeVideo $message)
    {
        $request = $this->repository->find($message->getRequestId());

        if ($request && !$request->isProcessed()) {
            try {
                $informations = $this->ytomp3->process($request->getYoutubeUrl(), function ($type, $buffer) use ($message) {
                    if (Process::OUT === $type) {
                        $buffer = array_map(static function (MatchResult $result) {
                            return $result->result();
                        }, Regex::matchAll('/[^\r\n]+/', $buffer)->results());
                        foreach ($buffer as $row) {
                            $this->bus->dispatch(new Update(
                                '/audio/request/' . $message->getRequestId() . '/output',
                                $row
                            ));
                        }
                    }
                });
                $audio = new Audio();
                $audio
                    ->setThumbnailUrl($informations['thumbnail'])
                    ->setTitle($informations['title'])
                    ->setArtist($informations['artist'])
                    ->setDisplayName($informations['displayName'])
                    ->setFilename($informations['filename'])
                    ->setMimeType($informations['mimeType']);

                $request
                    ->setAudio($audio)
                    ->setIsProcessed(true);

                $this->em->flush();
                $this->bus->dispatch(new Update(
                    '/audio/request/' . $message->getRequestId() . '/finish',
                    $this->serializer->serialize(compact('request'), 'json', ['groups' => ['mercure']])
                ));
            } catch (\Exception $exception) {
                $this->bus->dispatch(new Update(
                    '/audio/request/' . $message->getRequestId() . '/failed',
                    $this->serializer->serialize(['reason' => 'nolose'], 'json')
                ));
                $this->em->remove($request);
                $this->em->flush();
                throw new UnrecoverableMessageHandlingException();
            }

        } else {
            throw new NotFoundResourceException(
                sprintf('Could not find AudioRequest with ID "%s"', $message->getRequestId())
            );
        }
    }
}
