<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\Album;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArtistController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/artist/all', name: 'app_artist_all', methods: 'GET')]
    public function read(): JsonResponse
    {
        $listeSongArtist = [];
        $listeAlbumArtist = [];
        $listeUserArtist = [];
        $artistefind = false;

        // Récupérer tous les artistes, chansons, albums et l'utilisateur
        $artists = $this->entityManager->getRepository(Artist::class)->findAll();
        $songs = $this->entityManager->getRepository(Song::class)->findAll();
        $albums = $this->entityManager->getRepository(Album::class)->findAll();
        $current_user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        // Vérifier si l'utilisateur existe et si ses informations sont valides
        if (empty($current_user->getFirstname()) || empty($current_user->getLastname())) {
            return $this->json([
                'error' => true,
                'message' => 'Nom de l\'artiste manquants',
            ], 400);
        }
        if (!preg_match('/^\S+@\S+\.\S+$/', $current_user->getEmail())) {
            return $this->json([
                'error' => true,
                'message' => 'une ou plusieurs donnée son éronées',
            ], 409);
        }

        // Parcourir tous les artistes pour trouver celui correspondant à l'utilisateur
        foreach ($artists as $artist) {
            if ($artist->getUserIdUser()->getId() == $current_user->getId()) {
                $artistefind = true;

                // Parcourir toutes les chansons pour l'artiste trouvé et les ajouter à $listeSongArtist
                foreach ($songs as $song) {
                    if ($song->getIdSong() == $current_user->getId()) {
                        array_push($listeSongArtist, $song->Serializer());
                    }else{
                        array_push($listeSongArtist, $song->SerializerUser());
                    }
                }

                // Parcourir tous les albums pour l'artiste trouvé et les ajouter à $listeAlbumArtist
                foreach ($albums as $album) {
                    if ($album->getArtistUserIdUser()->getId() == $artist->getId()) {
                        array_push($listeAlbumArtist, $album->Serializer());
                    }else{
                        array_push($listeAlbumArtist, $album->Serializer());
                    }
                }
                break;
            }else{
                array_push($listeUserArtist, $current_user->Serializer());
            }
        }

        // Si l'artiste correspondant à l'utilisateur est trouvé
        if ($artistefind) {
            return $this->json([
                'error' => false,
                'artist' => $current_user ? $current_user->Serializer() : [],
                'song' => $listeSongArtist,
                'Album' => $listeAlbumArtist,
            ], 200);
        }
        return $this->json([
            'error' => false,
            'artist' => $listeUserArtist,
            'song' => $listeSongArtist,
            'Album' => $listeAlbumArtist,
        ], 200);
       
    }

    #[Route('/artist', name: 'app_artist', methods: 'POST')]
    public function readOne(Request $request): JsonResponse
    {
        if (!empty($request->get('label')) && !empty($request->get('fullname'))) {
            $current_user = $this->getUser()->getUserIdentifier();
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $current_user]);
            $artists = $this->entityManager->getRepository(Artist::class)->findAll();
            $currentDate = new DateTime();
            $age = $currentDate->diff($user->getBirthday());
            if (($age->y) < 16) {
                return $this->json([
                    'error' => true,
                    'message' => 'l\ age de l\ utilisateur de permet pas'

                ], 406);
            }
            foreach ($artists as $artist) {
                if ($artist->getUserIdUser()->getId() === $user->getId()) {
                    return $this->json([
                        'error' => true,
                        'message' => 'un compte utilisant est déja un compte artiste'

                    ], 409);
                }
            }
            if ($this->entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $request->get('fullname')])) {
                return $this->json([
                    'error' => true,
                    'message' => 'Un compte utilisant ce nom artiste déjà enregistré'

                ], 409);
            }
            //ajouter dans la data base
            $artist = new Artist();
            $artist->setFullname($request->get('fullname'));
            $artist->setUserIdUser($user);
            $artist->setDescription($request->get('description'));
            $artist->setLabel($request->get('label'));
            $this->entityManager->persist($artist);
            $this->entityManager->flush();
            return $this->json([
                'error' => false,
                'message' => 'votre inscription à bien été pris en compte'

            ], 409);
        } else {
            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes'
            ], 400);
        }
    }

}
