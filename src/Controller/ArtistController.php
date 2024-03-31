<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\Album;
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

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/artist/{token}', name: 'app_artist_all', methods: 'GET')]
    public function read(int $token): JsonResponse
    {
        $listeSongArtist = [];
        $listeAlbumArtist = [];
        $listeUserArtist = [];
        $artistefind = false;

        // Récupérer tous les artistes, chansons, albums et l'utilisateur
        $artists = $this->entityManager->getRepository(Artist::class)->findAll();
        $songs = $this->entityManager->getRepository(Song::class)->findAll();
        $albums = $this->entityManager->getRepository(Album::class)->findAll();
        $user = $this->entityManager->getRepository(User::class)->find($token);
        $usersAll = $this->entityManager->getRepository(User::class)->findAll();

        // Vérifier si l'utilisateur existe et si ses informations sont valides
        if ($user != null) {
            if (empty($user->getFirstname()) || empty($user->getLastname())) {
                return $this->json([
                    'error' => true,
                    'message' => 'Nom de l\'artiste manquants',
                ], 400);
            }
            if (!preg_match('/^\S+@\S+\.\S+$/', $user->getEmail())) {
                return $this->json([
                    'error' => true,
                    'message' => 'une ou plusieurs donnée son éronées',
                ], 409);
            }

            // Parcourir tous les artistes pour trouver celui correspondant à l'utilisateur
            foreach ($artists as $artist) {
                if ($artist->getUserIdUser()->getId() == $token) {
                    $artistefind = true;

                    // Parcourir toutes les chansons pour l'artiste trouvé et les ajouter à $listeSongArtist
                    foreach ($songs as $song) {
                        if ($song->getIdSong() == $token) {
                            array_push($listeSongArtist, $song->Serializer());
                        }
                    }

                    // Parcourir tous les albums pour l'artiste trouvé et les ajouter à $listeAlbumArtist
                    foreach ($albums as $album) {
                        if ($album->getArtistUserIdUser()->getId() == $artist->getId()) {
                            array_push($listeAlbumArtist, $album->Serializer());
                        }
                    }

                    break;
                }
            }

            // Si l'artiste correspondant à l'utilisateur est trouvé
            if ($artistefind) {
                return $this->json([
                    'error'=> false,
                    'artist' => $user ? $user->Serializer() : [],
                    'song' => $listeSongArtist,
                    'Album' => $listeAlbumArtist,
                ], 200);
            }

            // Si l'artiste correspondant à l'utilisateur n'est pas trouvé, renvoyer tous les artistes
            foreach ($artists as $artist) {
                foreach ($usersAll as $user) {
                    if ($artist->getUserIdUser()->getId() === $user->getId()) {
                        array_push($listeUserArtist, $user->Serializer());
                        break;
                    }
                }
                foreach ($songs as $song) {
                    if ($song->getIdSong() == $artist->getUserIdUser()->getId()) {
                        array_push($listeSongArtist, $song->SerializerUser());
                    }
                }
                foreach ($albums as $album) {
                    if ($album->getArtistUserIdUser()->getId() == $artist->getId()) {
                        array_push($listeAlbumArtist, $album->Serializer());
                    }
                }

            }
            return $this->json([
                'error'=>false,
                'artist' => $listeUserArtist,
                'song' => $listeSongArtist,
                'Album' => $listeAlbumArtist,
            ], 200);
        } else {
            return $this->json([
                'error' => true,
                'message' => 'votre token n\'est pas correct',
            ], 401);
        }
    }

    #[Route('/artist/{id}', name: 'app_artist', methods: 'GET')]
    public function readOne(string $id): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($id);

        $artistSerial = $artist->serializer();

        return $this->json([
            $artistSerial,
        ]);
    }

    #[Route('/artist', name: 'app_artist', methods: 'GET')]
    public function readName(Request $request): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($request->get('fullname'));

        $artistSerial = $artist->serializer();

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
        if ($request->get('fullname')) {
            $artist->setFullname($request->get('fullname'));
        }
        if ($request->get('label')) {
            $artist->setLabel($request->get('label'));
        }
        if ($request->get('description')) {
            $artist->setDescription($request->get('description'));
        }

        $this->entityManager->flush();
        return $this->json([
            'message' => 'Informations d Artiste modifiée correctement',
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

        $artist = $this->repository->findOneBy(["User_idUser" => $request->get('idUser')]);
        //$artistByName = $this->repository->findOneBy(["Fullname"=>$request->get('fullname')]);
        if (!$artist) {
            $user = $this->urepository->findOneBy(["idUser" => $request->get('idUser')]);

            $newArtist->setfullname($request->get('fullname'));
            $newArtist->setlabel($request->get('label'));
            $newArtist->setdescription($request->get('description'));
            $newArtist->setUserIdUser($user);
        } else {
            return $this->json([
                'message' => '',
                'path' => 'src/Controller/ArtistController.php'
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
