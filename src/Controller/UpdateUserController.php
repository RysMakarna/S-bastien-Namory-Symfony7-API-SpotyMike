<?php

namespace App\Controller;
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class UpdateUserController extends AbstractController
{
    #[Route('/update/user/{id}', name: 'app_update_user',methods: ['PUT'])]
    public function update(EntityManagerInterface $entityManager, int $id,Request $request): JsonResponse
    {
      $user = $entityManager->getRepository(User::class)->find($id);
      if (!$user) {
        return $this->json([
            'message' => 'Aucune compte avec ce id à modifier !',
        ]);
      }
      //les variable moddifiables
      $user->setName($request->get('name'));
      $user->setEmail($request->get('email'));
      $user->setTel($request->get('tel'));
      $entityManager->flush();
      return $this->json([
        'message'=> 'Ajouter avec succès',
      ]);
    }
}
