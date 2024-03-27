<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ReadAuserController extends AbstractController
{
    #[Route('/read/user', name: 'app_read_auser')]
    public function read(EntityManagerInterface $entityManager): JsonResponse
    {
       $user = $entityManager->getRepository(User::class)->findAll();
    
       $usersArray = array_map(function ($user) {
        return $user->UserSerializer(); // Ensure you have a toArray() method in your User entity
    }, $user);
       return $this->json([
            $usersArray,
        ]);
    }
}
