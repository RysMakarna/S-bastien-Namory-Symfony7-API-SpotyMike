<?php

namespace App\Controller;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Builder\Class_;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
    #[Route('/password-lost', name: 'app_read_user')]
    public function DeleteAcount(Request $request,JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $email_validation_regex = '/^\\S+@\\S+\\.\\S+$/';
        $email = $request->get('email');

        if(empty($email)){
            return $this->json([
                'error'=>true,
                'message'=> 'L\email manquant.Veuillez fornir votre mail pour la récupération du mot de passe.'
            ],400);
        }

        if(!preg_match($email_validation_regex,$email)){
            return $this->json([
            'error'=>true,
            'message'=> 'Le format de l \'email est invalide.Veuillez entrer un email valide'
            ],400);
        }
        //verifier si l'utilisateur à un email
        $current_user =$this->entityManager->getRepository(User::class)->findOneBy(['email'=> $email]);
        if($current_user == null){
            return $this->json([
                'error'=>true,
                'message'=> 'Aucun compte  n\'est associé à cet email.Veuillez  vérifier et réssayer'
            ],403);
        }
        $cache = new FilesystemAdapter();
        $cacheKey = 'reset_password_' . urlencode($email);
        $nbTentative = $cache->getItem($cacheKey);
        $allTentative = $nbTentative->get() ?? 0;
 
        $nbTentative->set($allTentative + 1);
        $nbTentative->expiresAfter(300); // 5minutes
        $cache->save($nbTentative);
        
        if($allTentative >= 3){
            return $this->json([
                'message' => 'Trop de demande de réinitialisation de mot de passe.Veuillez attendre avant de réessayer dans 5 minutes',
            ], 429);
        }
        $token = $this->tokenVerifier->generateToken($email,time()+120);//Génération du token 
        return $this->json([
            'success'=>true,
            'message' => 'Un email de réinitialisation de mot de passe à été envoyé à votre adresse email.Veuillez suivre les instructions contenues dans l\'email pour réinitialiser votre mot de passe.',
            'token'=> $token,  
        ], 200);
    }

    #[Route('/update/user/{id}', name: 'app_update_user', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /*$user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Aucune compte avec ce id à modifier !',
            ], 444);
        }
        $user->setEmail($request->get('email'));
        $user->setTel($request->get('tel'));
        $this->entityManager->flush();*/
        return $this->json([
            'message' => 'modifier avec succès',
        ], 200);
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
