<?php


namespace App\Controller\Api;


use App\Controller\ApiController;
use App\Entity\Audio;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/audio")
 */
class AudioController extends ApiController
{

    /**
     * @Route("/{id}/download", name="api.audio.download", methods={"GET"})
     */
    public function show(Audio $audio, S3Client $client, string $s3BucketName): Response
    {
        if ($audio->getFilename() && $audio->getMimeType()) {

            $command = $client->getCommand('GetObject', [
                'Bucket' => $s3BucketName,
                'Key' => $audio->getFilename(),
                'ResponseContentType' => $audio->getMimeType()
            ]);

            $request = $client->createPresignedRequest($command, '+1 minute');

            return new RedirectResponse(
                $request->getUri()
            );
        }

        throw $this->createNotFoundException();
    }

}
