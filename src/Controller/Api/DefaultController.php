<?php


namespace App\Controller\Api;


use App\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class DefaultController extends ApiController
{

    /**
     * @Route("/heartbeat", name="api.heartbeat", methods={"GET"})
     *
     * Just a dumb route to be called by the front because I'm using free Heroku dynos,
     * so I woke them up as early as possible
     */
    public function heartbeat(): Response
    {
        $this->dispatchMessage(new Update('/heartbeat', 'heartbeat'));

        return $this->json([
            'success' => true
        ]);
    }

}
