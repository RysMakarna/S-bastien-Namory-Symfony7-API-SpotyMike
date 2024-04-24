<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Song;
use App\Entity\Album;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;

class AlbumController extends AbstractController
{
    private $entityManager;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenService;
    }
    #[Route('/album', name: 'app_album', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {

        $currentUser = $this->getUser()->getUserIdentifier();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $currentUser]);
        $albumRepository = $this->entityManager->getRepository(Album::class);

        $existingAlbum = $albumRepository->findOneBy(['artist_User_idUser' => $user->getId(), 'name' => $request->get('name')]);

        if (empty($user)) {
            return $this->json([
                'error' => true,
                'message' => "Votre token n'est pas correct"
            ], 401);
        }

        if ($existingAlbum) {
            return $this->json([
                'error' => true,
                'message' => 'Ce nom a déjà été choisi',
            ], 409);
        }

        if (!$request->get('name') || !$request->get('categ')) {
            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données sont manquantes'
            ], 400);
        }

        #Check if data aren't unusable //Will need to add a REGEX for 'name' to block some words later
        if (!$this->isValidYear($request->get('year')) && is_array($request->get('songs'))) {
            return $this->json([
                'error' => true,
                'message' => '',
            ], 409);
        } //Will need to modify later with a library of categories


        $album = new Album();
        $albumId = "Album_" . rand(0, 999);
        $album->setIdAlbum($albumId);
        $album->setNom($request->get('name'));
        $album->setCateg($request->get('categ'));
        $album->setYear($request->get('year'));
        $albumCover = $request->get('cover') ? $request->get('cover') : "default_cover";
        $album->setCover($albumCover);
        $album->setCreateAt(new \DateTimeImmutable());
        //$album->setUpdateAt(new \DateTime()); on n'a pas besoin de update 
        $this->entityManager->persist($album);

        foreach ($request->get('songs') as $newSong) {
            $song = new Song();
            $song->setIdSong('Song_' . rand(0, 999));
            $song->setAlbum($album);
            $song->setTitle($newSong->get('title'));
            $songCover = $newSong->get('cover') ? $newSong->get('cover') : "default_cover";
            $song->setCover($songCover);
            $song->setUrl($newSong->get("url"));
            $visible = $newSong->get("visibility") == false ? false : true;
            $song->setVisibility($visible);
            $song->setCreateAt(new \DateTimeImmutable());
            // Will need to work on adding featuring later
            foreach ($song->getArtistIdUser() as $artist) {
                $artist->addSong($song);
                $this->entityManager->persist($artist);
            }
            $this->entityManager->persist($song);
        }

        $this->entityManager->flush();

        return $this->json([
            'error' => false,
            'message' => 'Album ajouté avec succès',
        ]);
    }
    #[Route('/album/{id}', name: 'put_album', methods: 'PUT')]
    public function putalbum(Request $request, int $id): JsonResponse
    {

        //array("rap","gospel","soul","country","hip hop","jazz","Mike","r\'n\'b");


        $currentUser = $this->tokenVerifier->checkToken($request, null);
        if (gettype($currentUser) == 'boolean') {
            return $this->tokenVerifier->sendJsonErrorToken();
        }
        //vérifier si l'album existe d'abord 
        $existeAlbum = $this->entityManager->getRepository(Album::class)->find(['id' => $id]);
        if (!$existeAlbum) {
            return $this->json([
                'error' => true,
                'message' => "Aucun album trouvé correspondant au non fourni."
            ], 404);
        }
        $totalParametres = $request->request->all();
        if (count($totalParametres) > 4) {
            return $this->json([
                'error' => true,
                'message' => "Les paramètres fournis sont invalides.Veuillez vérifier les données soumises."
            ], 400);
        }
        if (empty($totalParametres['title']) || empty($totalParametres['categorie']) || empty($totalParametres['cover']) || empty($totalParametres['visibility'])) {
            return $this->json([
                'error' => true,
                'message' => "Les paramètres fournis sont invalides.Veuillez vérifier les données soumises."
            ], 400);
        }
        if (!is_numeric($totalParametres['visibility']) || ($totalParametres['visibility'] != 0 && $totalParametres['visibility'] != 1)) {
            return $this->json([
                'error' => true,
                'message' => "La valeur du champ visibility est invalide.Les valeurs autorisées sont 0 pour invisible,1 pour visible."
            ], 400);
        }
        $jsoncategories = array(
            "rap",
            "gospel",
            "soul",
            "country",
            "hip hop",
            "jazz",
            "Mike",
            "r\'n\'b"
        );
       json_encode($totalParametres['categorie']);
        dd(json_encode($totalParametres['categorie']));
        

        if (isset($categories[$totalParametres['categorie']])) {
            dd("oopppp");
        }
        dd($totalParametres);
        return $this->json([]);
    }

    private function isValidYear($year)
    {
        if (is_numeric($year) && $year >= 1000 && $year <= 9999) {
            return true;
        }
        return false;
    }
}
