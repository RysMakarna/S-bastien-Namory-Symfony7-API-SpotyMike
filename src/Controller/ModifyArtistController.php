<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ModifyArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $urepository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->urepository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/modify/artist', name: 'app_modify_artist', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ModifyArtistController.php',
        ]);
    }
    
    #[Route('/modify/artist/{id}', name: 'app_modify_artist', methods: ['POST', 'PUT'])]
    public function modify(Request $request, string $id): JsonResponse
    {
        $artist = $this->entityManager->getRepository(artist::class)->find($id);
        if (!$artist) {
          return $this->json([
              'message' => 'Erreur',
          ]);
        }
        //les variable moddifiables
        if ($request->get('fullname')){
            $artist->setFullname($request->get('fullname'));
        }
        if ($request->get('label')){
            $artist->setLabel($request->get('label'));
        }
        if ($request->get('description')){
            $artist->setDescription($request->get('description'));
        }

        $this->entityManager->flush();
        return $this->json([
          'message'=> 'Informations d Artiste modifiée correctement',
        ]);
    }
}
