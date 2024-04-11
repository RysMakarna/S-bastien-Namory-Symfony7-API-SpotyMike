<?php
namespace App\EventListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTNotFoundListener
{
    public function onJWTNotFound(JWTNotFoundEvent $event)
    {
        $data = [
            'error'  => true,
            'message' => 'Le token est obligatoire',
        ];

        $response = new JsonResponse($data, 403);

        $event->setResponse($response);
    }
}
