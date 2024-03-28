<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AddArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $urepository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
        $this->urepository = $entityManager->getRepository(User::class);

    }

    #[Route('/add/artist', name: 'app_add_artist', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to our new Artist',
            'path' => 'src/Controller/AddArtistController.php',
        ]);
    }

    #[Route('/add/artist', name: 'app_add_artist', methods: ['POST', 'PUT'])]
    public function addArtist(Request $request): JsonResponse
    {
            $newArtist = new Artist();   
            //$newArtist = json_decode($request->getContent(), true);
            //$newArtist = urldecode($request->getContent());
            //$user = $this->getDoctrine()->getRepository(User::class)->find($newArtist['idUser']);
            //dump($request->get('idUser'));

            $artist = $this->repository->findOneBy(["User_idUser"=>$request->get('idUser')]);
            //$artistByName = $this->repository->findOneBy(["Fullname"=>$request->get('fullname')]);
            if (!$artist){
                $user = $this->urepository->findOneBy(["idUser"=>$request->get('idUser')]);
                
                $newArtist-> setfullname($request->get('fullname'));
                $newArtist-> setlabel($request->get('label'));
                $newArtist-> setdescription($request->get('description'));
                $newArtist-> setUserIdUser($user);
            }

            $this->entityManager->persist($artist);
            $this->entityManager->flush();

            return $this->json([
                'message' => "Welcome, !",
                'path' => 'src/Controller/AddArtistController.php',
            ]);
            
    }
}
