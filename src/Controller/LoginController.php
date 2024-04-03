<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $JWTManager;

    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $JWTManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->JWTManager = $JWTManager;

    }
    // pointless but need to test something
    #[Route('/login', name: 'app_login', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }

    #[Route('/login', name: 'app_login_post', methods: 'POST')]
    public function login(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $email_validation_regex = '/^\\S+@\\S+\\.\\S+$/';
        $email = $request->get('email');
        $password = $request->get('password');

        $user = $this->repository->findOneBy(['email' => $email]);

        //email et password vide

        if (empty($email) || empty($password)) {
            return $this->json([
                'error' => true,
                'message' => 'Email/password manquants'
            ], 400);
        }
        //email non comforme
        if (!preg_match($email_validation_regex, $email)) {
            return $this->json([
                'error' => true,
                'message' => 'Email/password incorrect'
            ], 400);
        }

        if ($user) {
            //password erroné
            if (!$passwordHash->isPasswordValid($user, $password)) {
                $nbt = $user->getnbTentative() + 1;
                $user->setnbTentative($nbt);
                //nombre de tentative
                if ($user->getnbTentative() >= 5) {
                    if ($user->getUpdateAt() && (time() - $user->getUpdateAt()->getTimestamp()) >= 120) {
                        $user->setnbTentative(0);
                        $user->setUpdateAt(new \DateTime());
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                    }
                    return $this->json([
                        'error' => true,
                        'message' => 'Trop de tentative sur email ' . $user->getEmail() . ' Veuillez patienter 2 minutes'
                    ], 429);

                }
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $this->json([
                    'error' => true,
                    'message' => 'Email/password incorrect'
                ], 400);
            }
        } else {
            return $this->json([
                'error' => true,
                'message' => 'Email/password incorrect'
            ], 400);
        }
        return $this->json([
            'error' => false,
            'data' => $user->serializer(),
            'token' => $this->JWTManager->create($user)
        ], 200);

    }
}

