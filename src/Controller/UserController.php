<?php

namespace App\Controller;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;

class UserController extends AbstractController
{
    private $entityManager;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager, TokenService  $tokenService)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenService;
    }

    #[Route('/read/user', name: 'app_read_user')]
    public function readUser(): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findAll();
        if (count($user) > 0) {
            $usersArray = array_map(function ($user) {
                return $user->UserSerializer(); // Ensure you have a toArray() method in your User entity
            }, $user);

            return $this->json([
                $usersArray,
            ], 200);
        }
        return $this->json([
            'message' => 'aucun utilisateur pour le moment',
        ], 204);

    }

    #[Route('/user', name: 'app_update_user', methods: ['POST'])]
    public function readOne(Request $request): JsonResponse
    {
        $currentUser = $this->tokenVerifier->checkToken($request);
        if (gettype($currentUser) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken());
        }
        $repository = $this->entityManager->getRepository(User::class);
        $otherUser = $repository->findOneBy($request->get('tel'));

        if ($currentUser->getEmail() != $otherUser->getEmail()){
            return $this->json([
                'error'=> true,
                "message"=>"Conflit de données. Le numéro est déjà utilisé par un autre utilisateur.",
                ],409);
        }
        if(!preg_match('^0[1-7][0-9]{8}$^', $request->get('tel'))){
            return $this->sendErrorMessage400(1);
        }
        $sexe = strtolower($request->get('sexe')) == 'homme' ? 0 : (strtolower($request->get('sexe')) == 'femme' ? 1 : (strtolower($request->get('sexe')) == 'non-binaire' ? 2 : null));
        if ($sexe === null) {
            return $this->sendErrorMessage400(2);
        }
        
        if(!$request->get('firstname') || !$request->get('lastname') || !preg_match('/^[a-zA-ZÀ-ÿ\-]+$/', $request->get('firstname')) || !preg_match('/^[a-zA-ZÀ-ÿ\-]+$/', $request->get('lastname'))){
            return $this->sendErrorMessage400(3);
        }
        

    }


    #[Route('/delete/user/{id}', name: 'app_delete_user', methods: ['delete'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Aucune compte avec ce id à modifier !',
            ], 444);
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utisateur supprimer avec succès!',
        ], 200);
    }

    private function sendErrorMessage400(int $errorCode){

    }

}
