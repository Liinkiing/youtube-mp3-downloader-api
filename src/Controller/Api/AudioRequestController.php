<?php


namespace App\Controller\Api;


use App\Controller\ApiController;
use App\Entity\AudioRequest;
use App\Form\AudioRequestType;
use App\Message\Command\ProcessYouTubeVideo;
use App\Repository\AudioRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(Request $request, AudioRequestRepository $repository, EntityManagerInterface $em): Response
    {
        $youtubeUrl = $request->query->get('youtube_url');

        if ($youtubeUrl) {
            $match = $repository->findOneBy([
                'youtubeUrl' => $youtubeUrl
            ]);
            if (!$match) {
                return $this->json(null);
            }
            if ($match->isProcessed()) {
                return $this->json(
                    $match
                );
            }

            $em->remove($match);
            $em->flush();
            return $this->json(null);
        }

        return $this->json(
            $repository->findAll()
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

    /**
     * @Route("/requests", name="api.audio_request.new", methods={"POST"})
     */
    public function new(Request $request, FormFactoryInterface $formFactory, EntityManagerInterface $em): Response
    {
        $audioRequest = new AudioRequest();
        $form = $formFactory->create(AudioRequestType::class, $audioRequest);

        $form->submit(
            json_decode($request->getContent(), true)
        );

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($audioRequest);
            $em->flush();

            $this->dispatchMessage(new ProcessYouTubeVideo(
                $audioRequest->getId()
            ));

            return $this->json(
                $audioRequest,
                Response::HTTP_CREATED
            );
        }
        return $this->json(
            [
                'errors' => $this->createFormErrors($form)
            ],
            Response::HTTP_BAD_REQUEST
        );
    }

}
