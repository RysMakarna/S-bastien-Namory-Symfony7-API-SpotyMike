<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\Album;
use App\Entity\Label;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenService;
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
                    } else {
                        array_push($listeSongArtist, $song->SerializerUser());
                    }
                }

                // Parcourir tous les albums pour l'artiste trouvé et les ajouter à $listeAlbumArtist
                foreach ($albums as $album) {
                    if ($album->getArtistUserIdUser()->getId() == $artist->getId()) {
                        array_push($listeAlbumArtist, $album->Serializer());
                    } else {
                        array_push($listeAlbumArtist, $album->Serializer());
                    }
                }
                break;
            } else {
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
        $regex_idLabel = '/^12[0-9][a-zA-Z]$/';
        $currentUser = $this->tokenVerifier->checkToken($request);
        if (gettype($currentUser) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken());
        }
        if (empty($request->get('id_label')) || empty($request->get('fullname'))) {
            return $this->json([
                'error' => true,
                'message' => 'l\'id du label et le fullname sont obligatoires.',
            ], 400);
        }
        ;
        //verification du format de id_label 
        if (!preg_match($regex_idLabel, $request->get('id_label'))) {
            return $this->json([
                'error' => true,
                'message' => 'le format de l\'id du label est invalide.',
            ], 400);
        }
        $label = $this->entityManager->getRepository(Label::class)->findOneBy(['id_label' => $request->get('id_label')]);
        if ($label === null) {
            return $this->json([
                'error' => true,
                'message' => 'ce lablel n\'existe pas.',
            ], 404);
        }
        $currentDate = new DateTime();
        $age = $currentDate->diff($currentUser->getBirthday());
        if (($age->y) < 16) {
            return $this->json([
                'error' => true,
                'message' => 'l\'age de l\'utilisateur de permet pas'

            ], 406);
        }
        $artist = $this->entityManager->getRepository(Artist::class)->findOneBySomeField($currentUser->getId());
        if ($artist != null) {
            return $this->json([
                'error' => true,
                'message' => 'l\'utilisateur ne peut créer qu\'un seul compte.Veuillez supprimer le compte existatnt pour créer un nouveau'

            ], 403);
        }
        $artistFullname = $this->entityManager->getRepository(Artist::class)->GetExiteFullname($request->get('fullname'));
        if ($artistFullname[1] != 0) {
            return $this->json([
                'error' => true,
                'message' => 'ce nom d\'artist existe déja.Veuillez choisir un autre'
            ], 409);
        }

        $newArtist = new Artist();
        $newArtist->setFullname($request->get('fullname'));
        $newArtist->setUserIdUser($currentUser);
        $this->entityManager->persist($newArtist);
        $this->entityManager->flush();
        $artistId = $this->entityManager->getRepository(Artist::class)->findOneBySomeField($currentUser->getId());
        return $this->json([
            'error' => false,
            'message' => 'Votre compte artiste a été créé avec succès. Bienvenue dans notre communauté d\'artiste!',
            'artist_id' => $artistId->getId(), // Supposant que l'ID de l'artiste est 1, ajustez selon la logique appropriée
        ], 200); // Utilisez 200 pour indiquer le succès
    }

}
