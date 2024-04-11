<?php

namespace App\Controller;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
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

}
