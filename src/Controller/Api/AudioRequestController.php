<?php


namespace App\Controller\Api;


use App\Controller\ApiController;
use App\Entity\AudioRequest;
use App\Form\AudioRequestType;
use App\Message\Command\ProcessYouTubeVideo;
use App\Repository\AudioRequestRepository;
use App\Serializer\FormErrorsSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/audio")
 */
class AudioRequestController extends ApiController
{

    private $bus;

    public function __construct(FormErrorsSerializer $formErrorsSerializer, MessageBusInterface $bus)
    {
        parent::__construct($formErrorsSerializer);
        $this->bus = $bus;
    }

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

            $this->bus->dispatch(new ProcessYouTubeVideo(
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
