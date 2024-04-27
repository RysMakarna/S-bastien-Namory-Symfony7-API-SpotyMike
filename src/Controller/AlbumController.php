<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AlbumController extends AbstractController
{
    private $entityManager;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenService;
    }

    #[Route('/album', name: 'app_album')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AlbumController.php',
        ]);
    }

    #[Route('/album', name: 'add_album', methods: ['POST'])]
    public function addAlbum(Request $request): JsonResponse
    {
        $currentUser = $this->tokenVerifier->checkToken($request);
        $urepository = $this->entityManager->getRepository(Artist::class);
        $alrepository = $this->entityManager->getRepository(Artist::class);

        if (gettype($currentUser) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken());
        }
        $artist = $urepository->findOneBy(["User_idUser" => $currentUser->getId()]);
        if ($artist->getActif() === 0)
            return $this->json([
                "error" => true,
                "message" => "Vous n'avez pas l'autorisation pou accéder à cet album.",
            ], 403);

        parse_str($request->getContent(), $albumData);

        $explodeData = explode(",", $albumData['avatar']);

        $this->verifyKeys($albumData, 2) == true ? true : $this->sendError400(1);


        if (count($explodeData) == 2) {
            # Verify File Extension
            $reexplodeData = explode(";", $explodeData[0]);
            $fileExt = explode("/", $reexplodeData[0]);

            $fileExt[1] == "png" ? "png" : ($fileExt[1] == "jpeg" ? "jpeg" : $this->sendError422(2));

            $base64IsValid = base64_decode($explodeData[1], true);
            # Check if Base64 string can be decoded
            if ($base64IsValid === false) {
                return $this->sendError422(1);
            }
            $file = base64_decode($explodeData[1]);

            # Check if file size is correct
            $fileSize = ((strlen($file) * 0.75) / 1024) / 1024;
            if (number_format($fileSize, 1) < 1.0 || number_format($fileSize, 1) >= 8.0) {
                return $this->sendError422(3);
            }

            $chemin = $this->getParameter('upload_directory') . '/' . $artist->getFullname() . '/' . $albumData['title'];
            mkdir($chemin);
            file_put_contents($chemin . '/Cover.' + $fileExt[1], $file);
        }

        if (preg_match("", $albumData['title']) || preg_match("", $albumData['categorie'])) {
            return $this->sendError422(4);
        }

        $otherAlbum = $alrepository->findOneBy(["nom" => $albumData['title']]);
        if ($otherAlbum)
            return $this->json([
                "error" => true,
                "message" => "Ce titre est déjà pris. Veuillez en choisir un autre.",
            ], 409);

        $this->verifyCateg($albumData['categorie']) == true ? true : $this->sendError400(3);

        $albumData['visibility'] == "0" ? 0 : ($albumData['visibility'] == "1" ? 1 : $this->sendError400(2));


        $album = new Album();
        $album->setNom($albumData['title']);
        $album->setActif($albumData['visibility']);
        $album->setCateg($albumData['categorie']);

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $this->json([
            "error" => false,
            'message' => "Album créé avec succès.",
            'id' => $album->getId(), // Supposant que l'ID de l'artiste est 1, ajustez selon la logique appropriée
        ], 201);

    }
    #[Route('/album/{id}', name: 'put_album', methods: ['PUT'])]
    public function putalbum(Request $request, int $id): JsonResponse
    {
        $currentUser = $this->tokenVerifier->checkToken($request, null);
        if (gettype($currentUser) == 'boolean') {
            return $this->tokenVerifier->sendJsonErrorToken();
        }
        //vérifier si l'album existe d'abord 
        $existeAlbum = $this->entityManager->getRepository(Album::class)->find(['id' => $id]);
        if (!$existeAlbum) {
            return $this->json([
                'error' => true,
                'message' => "Aucun album trouvé correspondant au nom fourni."
            ], 404);
        }
        $param = $this->allowedKeys($request);
        if (gettype($param) == 'boolean') {
            return $this->sendError400(1);
        }
        if ($param === []) {
            return $this->sendError400(1);
        }
        if (array_key_exists('visibility', $param)) {
            if (!is_numeric($param['visibility']) || ($param['visibility'] != 0 && $param['visibility'] != 1)) {
                return $this->sendError400(2);
            }
            if($this->checkOwner($currentUser, $existeAlbum)!=null){
                return $this->checkOwner($currentUser, $existeAlbum);
            }
            //modifier dans la database 
            $updateVisibility = $existeAlbum->setVisibility($param['visibility']);
            $this->entityManager->persist($updateVisibility);
            $this->entityManager->flush();

        }
        if (array_key_exists('categorie', $param)) {
            //Vérification qu'il s'agit d'un ficher Json
            $regexValiderJson = '/^\[\s*("[^"]*"|\s*)\s*(?:,\s*("[^"]*"|\s*)\s*)?\]$/';
            if (!preg_match($regexValiderJson, $param['categorie'])) {
                return $this->sendError422(4);
            }
            $jsoncategories = json_decode($param['categorie']);
            if ($this->verifyCateg($jsoncategories) === false) {
                return $this->sendError400(3);
            }
           if($this->checkOwner($currentUser, $existeAlbum)!=null){
            return $this->checkOwner($currentUser, $existeAlbum);
           }
            $updateCategory = $existeAlbum->setCateg($param['categorie']);
            $this->entityManager->persist($updateCategory);
            $this->entityManager->flush();
        }
        if (array_key_exists('title', $param)) {
            $regexValidatedTitle = '/^[\w\s!@#$%^&*()_+\-=\[\]{};:\'"\\\\|,.<>\/?]{1,90}$/';
            //curéperation de l'albume d'abord
            if (!preg_match($regexValidatedTitle, $param['title'])) {
                return $this->sendError422(4);
            }
            if($this->checkOwner($currentUser, $existeAlbum)!=null){
                return $this->checkOwner($currentUser, $existeAlbum);
               }
            //récupérer toute les alums de ce artiste
            $allAlbumCurrentUser = $this->entityManager->getRepository(Album::class)->allAlbumForCurrentUser($currentUser->getArtist()->getId());
            //dd($allAlbumCurrentUser);      
            foreach ($allAlbumCurrentUser as $album) {
                if ($album->getNom() == $param['title']) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Ce titre est déjà pris.Veuillez en choisir un autre.'
                    ], 409);
                }
            }
            $updateTitle = $existeAlbum->setNom($param['title']);
            $this->entityManager->persist($updateTitle);
            $this->entityManager->flush();

        }
        if (array_key_exists('cover', $param)) {
            //vérifier qu'il s'agit d'une base 64..
            $regexBase64 = '~^data:image/([a-zA-Z]*);base64,([^\s]+)$~';
            if (!preg_match($regexBase64, $param['cover'])) {
                return $this->sendError422(1);
            }
            $explodeData = explode(",", $param['cover']);
            $file = $explodeData[1];

            $validedFormat = base64_decode($file);
            $fileSize = ((strlen($file) * 0.75) / 1024) / 1024;
            $reexplodeData = explode(";", $explodeData[0]);
            $fileExt = explode("/", $reexplodeData[0]);
            $fileExtension = explode(";", $fileExt[1]);
            if ($fileExtension[0] !== "png" && $fileExtension[0] !== "jpeg") {
                return $this->sendError422(2);
            }/*
           if($fileSize<1.0 || $fileSize >8.0){
               return $this->sendError422(3);
           }*/
           if($this->checkOwner($currentUser, $existeAlbum)!=null){
            return $this->checkOwner($currentUser, $existeAlbum);
           }
            $chemin = $this->getParameter('upload_directory') . '/' . $existeAlbum->getArtistUserIdUser()->getFullname();
            mkdir($chemin);
            file_put_contents($chemin . '/file.png', $validedFormat);
            //enregistrer avec le nom artiste..

        }
        return $this->json([
            'error' => false,
            'message' => 'Album mis à jour avec succès.'
        ]);


    }

    private function verifyKeys($requestBody, int $obli)
    {
        switch ($obli) {
            case 1:
                $obligatoryKeys = ['visibility', 'cover', "title", "categorie"];
                $keys = array_keys($requestBody);
                $resultGood = 0;
                foreach ($keys as $key) {
                    if (in_array($key, $obligatoryKeys)) {
                        $resultGood++;
                    } else {
                        $resultGood = 0;
                    }
                }
                if ($resultGood < 4) {
                    return false;
                }
                return true;
            case 2:
                $allowedKeys = ['visibility', 'cover', "title", "categorie"];
                $keys = array_keys($requestBody);
                foreach ($keys as $key) {
                    if (!in_array($key, $allowedKeys)) {
                        return false;
                    }
                }

        }
    }

    private function allowedKeys($request)
    {
        $totalParametres = $request->request->all();
        if (count($totalParametres) > 4) {
            return true;
        }
        $allowedKeys = ['visibility', 'cover', "title", "categorie"];
        $tableau = [];
        foreach ($totalParametres as $key => $value) {
            if (!in_array($key, $allowedKeys)) {
                return true;
            } elseif (!empty($value)) {
                $tableau[$key] = $value;
            }
        }
        return $tableau;
    }
    private function checkOwner($currentUser, $existeAlbum)
    {
        $is_artist = $currentUser->getArtist();//l'artiste 1
        $idAlbum = $existeAlbum->getArtistUserIdUser()->getId();
        if ($is_artist == null || $is_artist->getActif() == 0 || $is_artist->getId() != $idAlbum) {
            return $this->json([
                'error' => true,
                'message' => 'Vous n\'avez pas l\'autorisation pour accéder à cet album.'
            ], 403);
        }
    }

    private function verifyCateg($categorie)
    {
        $categContent = ["rap", "r'n'b", "gospel", "soul", "country", "hip hop", "jazz", "le Mike"];
        if (count($categorie) >= 2) {
            for ($i = 0; $i < count($categorie); $i++) {
                if (!in_array($categorie[$i], $categContent)) {
                    return false;
                }
            }
        } else {
            if (!in_array($categorie[0], $categContent)) {
                return false;
            }
        }

    }

    private function sendError422(int $errorCode)
    {
        switch ($errorCode) {
            case 1:
                return $this->json([
                    "error" => true,
                    "message" => "Le serveur ne peut pas décoder le contenu base64 en fichier binaire.",
                ], 422);
            case 2:
                return $this->json([
                    "error" => true,
                    "message" => "Erreur sur le format du fichier qui n'est pas pris en compte.",
                ], 422);
            case 3:
                return $this->json([
                    "error" => true,
                    "message" => "Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.",
                ], 422);
            case 4:
                return $this->json([
                    "error" => true,
                    "message" => "Erreur de validation des données.",
                ], 422);
        }
    }

    private function sendError400(int $errorCode)
    {
        switch ($errorCode) {
            case 1:
                return $this->json([
                    "error" => true,
                    "message" => "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.",
                ], 400);
            case 2:
                return $this->json([
                    "error" => true,
                    "message" => "La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible."
                ], 400);
            case 3:
                return $this->json([
                    'error' => true,
                    'message' => "Les catégories ciblées sont invalides.",
                ], 400);
        }
    }
}
