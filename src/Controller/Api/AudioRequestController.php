<?php


namespace App\Controller\Api;


use App\Controller\ApiController;
use App\Entity\AudioRequest;
use App\Repository\AudioRequestRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/audio")
 */
class AudioRequestController extends ApiController
{

    /**
     * @Route("/requests", name="api.audio_request.index", methods={"GET"})
     */
    public function index(AudioRequestRepository $repository): Response
    {
        $requests = $repository->findAll();

        return $this->json(
            $requests
        );
    }

    /**
     * @Route("/request/{id}", name="api.audio_request.show", methods={"GET"})
     */
    public function show(AudioRequest $request): Response
    {
        return $this->json(
            $request
        );
    }
}
