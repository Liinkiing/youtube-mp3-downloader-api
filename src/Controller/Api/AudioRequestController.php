<?php


namespace App\Controller\Api;


use App\Controller\ApiController;
use App\Repository\AudioRequestRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/requests/audio")
 */
class AudioRequestController extends ApiController
{

    /**
     * @Route("/", name="api.audio_request.index")
     */
    public function index(AudioRequestRepository $repository): Response
    {
        $requests = $repository->findAll();

        return $this->json(
            $requests
        );
    }
}
