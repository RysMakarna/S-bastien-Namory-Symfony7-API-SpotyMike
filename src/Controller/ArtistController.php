<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $urepository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(artist::class);
        $this->urepository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/artist/all', name: 'app_artist_all', methods: 'GET')]
    public function read(): JsonResponse
    {
       $artist = $this->entityManager->getRepository(Artist::class)->findAll();
    
       $artistsArray = array_map(function ($artist) {
        return $artist->artistSerializer(); // Ensure you have a toArray() method in your artist entity
    }, $artist);
       return $this->json([
            $artistsArray,
        ]);
    }

    #[Route('/artist/{id}', name: 'app_artist', methods: 'GET')]
    public function readOne(string $id): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($id);

        $artistSerial = $artist->ArtistSerializer();

        return $this->json([
            $artistSerial,
        ]);
    }

    #[Route('/artist', name: 'app_artist', methods: 'GET')]
    public function readName(Request $request): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($request->get('fullname'));

        $artistSerial = $artist->ArtistSerializer();

        return $this->json([
            $artistSerial,
        ]);
    }

    #[Route('/artist/{id}', name: 'app_artist_modify', methods: 'PUT')]
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

    #[Route('/add/artist', name: 'app_artist_add', methods: 'POST')]
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
            } else {
                return $this->json([
                    'message'=>'',
                    'path'=> 'src/Controller/ArtistController.php'
                ]);
            }

            $this->entityManager->persist($artist);
            $this->entityManager->flush();

            return $this->json([
                'message' => "Welcome, !",
                'path' => 'src/Controller/ArtistController.php',
            ]);

    }
}
