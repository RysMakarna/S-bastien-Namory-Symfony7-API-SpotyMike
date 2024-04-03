<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AlbumController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/album', name: 'app_album')]
    public function index(Request $request): JsonResponse
    {

        $currentUser = $this->getUser()->getUserIdentifier();
        $albumRepository = $this->entityManager->getRepository(Album::class);
        $existingAlbum = $albumRepository->findOneBy(['artist_User_idUser'=> $currentUser->getId(), 'name'=>$request->get('name')]); 

        if(empty($currentUser)) {
            return $this-> json([
            'error' => true,
            'message' => "Votre token n'est pas correct"
            ], 401);
        }
        
        if($existingAlbum){
            return $this->json([
                'error' => true,
                'message' => 'Ce nom a déjà été choisi',
                ],409);
        }

        if (!$request->get('name') || !$request->get('catef')) {
            return $this->json([
                'error'=> true,
                'message'=> 'Une ou plusieurs données sont manquantes'
            ], 400);
        }

        #Check if data aren't unusable //Will need to add a REGEX for 'name' to block some words later
        if (!$this->isValidYear($request->get('year')) && is_array($request->get('songs'))) {
            return $this->json([
                'error'=> true,
                'message' => '',
                ],409);
        } //Will need to modify later with a library of categories


        $album = new Album();
        $albumId = "Album_".rand(0,999);
        $album -> setIdAlbum($albumId);
        $album->setNom($request->get('name'));
        $album->setCateg($request->get('categ'));
        $album->setYear($request->get('year'));
        $albumCover = $request->get('cover') ? $request->get('cover') : "default_cover";
        $album->setCover($albumCover);
        $album->setCreateAt(new \DateTimeImmutable());
        $album->setUpdateAt(new \DateTime());
        $this->entityManager->persist($album);

        foreach($request->get('songs') as $newSong){
            $song = new Song();
            $song->setIdSong('Song_'.rand(0,999));
            $song->setAlbum($albumId);
            $song->setTitle($newSong->get('title'));
            $songCover = $newSong->get('cover') ? $newSong->get('cover') : "default_cover";
            $song->setCover($songCover);
            $song->setUrl($newSong->get("url"));
            $visible = $newSong->get("visibility") == false ? false : true;
            $song-> setVisibility($visible);
            $song->setCreateAt(new \DateTimeImmutable());
            // Will need to work on adding featuring later
            /*foreach($song->getArtistIdUser() as $artist){
                $artist->addSong($song);
                $this->entityManager->persist($artist);
            }*/
            $this->entityManager->persist($song);
        }

        $this->entityManager->flush();

        return $this->json([
            'error' => false,
            'message' => 'Album ajouté avec succès',
        ]);
    }

    private function isValidYear($year){
        if(is_numeric($year) && $year >= 1000 && $year <= 9999) {
            return true;
        }
        return false;
    }
}
