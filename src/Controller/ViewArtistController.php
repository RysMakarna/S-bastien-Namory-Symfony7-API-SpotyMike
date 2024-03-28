<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ViewArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $urepository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(artist::class);
        $this->urepository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/read/artist', name: 'app_read_artist', methods: 'GET')]
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

    #[Route('/read/artist/{id}', name: 'app_read_artist', methods: 'GET')]
    public function readOne(string $id): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($id);

        $artistSerial = $artist->ArtistSerializer();

        return $this->json([
            $artistSerial,
        ]);
    }

    #[Route('/read/artist/', name: 'app_read_artist', methods: 'GET')]
    public function readName(Request $request): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($request->get('fullname'));

        $artistSerial = $artist->ArtistSerializer();

        return $this->json([
            $artistSerial,
        ]);
    }
}
