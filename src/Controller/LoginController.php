<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
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

    #[Route('/login', name: 'app_login_post', methods: ['POST', 'PUT'])]
    public function login(Request $request): JsonResponse
    {
        $user = $this->repository->findOneBy(["email" => "slopez@orange.fr"]);
        return $this->json([
            'user' => json_encode($user),
            'data' => $request->getContent(),
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }

    #[Route('/add/user', name: 'app_login_post', methods: ['POST'])]
    public function AddUser(Request $request): JsonResponse
    {
        $id_user = $this->repository->count();
        $email = $request->get('email');
        $existingUser = $this->repository->findOneBy(['Email' => $email]);

        if ($existingUser) {
            return $this->json([
                "message" => 'L utilisateur existe',
            ]);
        }
        $user = new User();
        $user->setEmail($request->get('email'));
        $user->setName($request->get('name'));
        //$encrypte = password_hash($request->get('encrypte'), PASSWORD_DEFAULT);        
        $user->setEncrypte($request->get('encrypte'));
        $user->setTel($request->get('tel'));
        $user->setIdUser($id_user+1);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'user' => $user->UserSerializer(),
            'message' => 'Ajouter  avec  succès',
        ]);

    }
}
