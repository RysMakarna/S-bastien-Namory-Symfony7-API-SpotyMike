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
    public function update(Request $request): JsonResponse
    {
        $currentUser = $this->tokenVerifier->checkToken($request);
        if (gettype($currentUser) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken());
        }
        $repository = $this->entityManager->getRepository(User::class);
        

        if(!$request->get('firstname') || !$request->get('lastname') || !$request->get('tel') || $request->get('sexe') === null){
            return $this->json([
                "error"=> true,
                "message"=> "Erreur de validation des données.",
            ], 422);
        }
        
        if(!preg_match('^0[1-7][0-9]{8}$^', $request->get('tel'))){
            return $this->sendErrorMessage400(1);
        }

        $otherUser = $repository->findOneBy(["tel" => $request->get('tel')]);
        if ($currentUser->getEmail() != $otherUser->getEmail()){
            return $this->json([
                'error'=> true,
                "message"=>"Conflit de données. Le numéro est déjà utilisé par un autre utilisateur.",
                ],409);
        }
        $sexe = $request->get('sexe') === '0' ? 0 : ($request->get('sexe') === '1' ? 1 : ($request->get('sexe') === '2' ? 2 : null));
        if ($sexe === null) {
            return $this->sendErrorMessage400(2);
        }
        
        if(!preg_match('/^[a-zA-ZÀ-ÿ\-]+$/', $request->get('firstname')) || !preg_match('/^[a-zA-ZÀ-ÿ\-]+$/', $request->get('lastname'))){
            return $this->sendErrorMessage400(3);
        }
        $currentUser->setFirstname($request->get('firstname'));
        $currentUser->setLastname($request->get('lastname'));
        $currentUser->setTel($request->get('tel'));
        $currentUser->setSexe($sexe);

        $this->entityManager->persist($currentUser);
                $this->entityManager->flush();
                return $this->json([
                    'error' => false,
                    'message' => "Votre inscription a bien été prise en compte.",
                ], 201);
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
        switch($errorCode){
            case 1:
                return $this->json([
                    "error" => true,
                    "message" => "Le format du numéro de téléphone est invalide",
                ], 400);
            case 2:
                return $this->json([
                    "error"=> true,
                    "message"=> "La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme, 2 pour Non-Binaire",
                ], 400);
            case 3:
                return $this->json([
                    "error" => true,
                    "message" => "Les données fournies sont invalides ou incomplètes",
                ], 400);
        } 
    }

}
