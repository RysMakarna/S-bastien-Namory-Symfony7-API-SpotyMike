<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/add/user', name: 'app_add_user', methods: ['POST'])]
    public function AddUser(Request $request): JsonResponse
    {
        $id_user = $this->repository->count();
        $email = $request->get('email');
        $existingUser = $this->repository->findOneBy(['email' => $email]);

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
        $user->setCreateAt(new \DateTimeImmutable());
        $user->setUpdateAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'user' => $user->UserSerializer(),
            'message' => 'Ajouter  avec  succès',
        ]);

    }

    #[Route('/read/user', name: 'app_read_user')]
    public function readUser(): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findAll();

        $usersArray = array_map(function ($user) {
            return $user->UserSerializer(); // Ensure you have a toArray() method in your User entity
        }, $user);

        return $this->json([
          $usersArray,
        ]);
    }

    #[Route('/update/user/{id}', name: 'app_update_user',methods: ['PUT'])]
    public function update(int $id,Request $request): JsonResponse
    {
      $user = $this->entityManager->getRepository(User::class)->find($id);
      if (!$user) {
        return $this->json([
            'message' => 'Aucune compte avec ce id à modifier !',
        ]);
      }
      $user->setName($request->get('name'));    
      $user->setEmail($request->get('email'));
      $user->setTel($request->get('tel'));
      $this->entityManager->flush();
      return $this->json([
        'message'=> 'modifier avec succès',
      ]);
    }
    #[Route('/delete/user/{id}', name: 'app_delete_user',methods:['delete'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Aucune compte avec ce id à modifier !',
            ]);
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utisateur supprimer avec succès!',
        ]);
    }

}
