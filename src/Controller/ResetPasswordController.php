<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class ResetPasswordController extends AbstractController
{
    private $tokenVerifier;
    private $entityManager;
    public function __construct(TokenService $tokenService,EntityManagerInterface $entityManager)
    {
        $this->tokenVerifier = $tokenService;
        //$this->jwtProvider = $jwtProvider;
        $this->entityManager = $entityManager;

    }
    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['POST', 'GET'])]
    public function index(string $token,UserPasswordHasherInterface $passwordHash,Request $request): JsonResponse
    {
        if(strlen($token)<801){
            return $this->json([
                'error' =>true, 
                'message' => 'Token de réinitialisation manquant ou invalide.Veuillez utiliser le lien fourni dans l\'email de réinitialisation de mot de passe',
            ],400);
        }
        $token = $request->get("token");
        $currentUser = $this->tokenVerifier->checkToken($request, $token);
        if (gettype($currentUser) == 'boolean'){    
            return $this->json([
                'error'=>true,
                'Message'=>'Votre token de réinitialisation de mot de passe à expiré.Veuillez refaire une demande de reinitialisation de mot de passe '
            ],400);
        }
        $user = $currentUser;
        $password = $request->get('password');
        $password_pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,20}$/';
        if(empty($password)){
            return $this->json([
                'error'=>true,
                'message'=> 'Veuillez fourni le nouveau mot de passe'
            ],400);
        }
        if (!preg_match($password_pattern, $password) || strlen($password)< 8){
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule,une minuscule ,un chiffre,une caractère spécial et avoir 8 caractères minimun '
            ], 400);
        }
        $hash = $passwordHash->hashPassword($user, $password) ;
        $user->setPassword($hash);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        return $this->json([
            'error'=>false,
            'message' => 'Votre mot de passe à été enregistrer avec succès.Vous pouvez vous connnecter avec  notre nouveau mot de passe ',
        ],200);
    }
};
