<?php

namespace App\Controller;

use App\Entity\ArtistHasLabel;
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
    #[Route('/artist', name: 'app_artist_all', methods: 'GET')]
    public function read(Request $request): JsonResponse
    {
        $currentUser = $this->tokenVerifier->checkToken($request,null);
        if (gettype($currentUser) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken());
        }
        return $this->json([
            'error' => true,
            'artist' => $currentUser->UserSerializer()
        ], 409);
        
    }

    #[Route('/artist', name: 'app_artist', methods: 'POST')]
    public function readOne(Request $request): JsonResponse
    {
        $regex_idLabel = '/^12[0-9][a-zA-Z]$/';
        $currentUser = $this->tokenVerifier->checkToken($request,null);
        $urepository = $this->entityManager->getRepository(Artist::class);
        if (gettype($currentUser) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken());
        }
        $artist = $urepository->findOneBy(["User_idUser" => $currentUser->getId()]);
        if ($artist) {
            if ($artist->getActif() === 0) {
                return $this->json([
                    "error" => true,
                    "message" => "VOus n'êtes pas autorisé à accéder aux informations de cet artiste.",
                ], 403);
            }

            if ($request->get('fullname')) {
                $otherArtist = $urepository->findOneBy(["fullname" => $request->get("fullname")]);
                if ($artist->getUserIdUser() != $otherArtist->getUserIdUser()) {
                    return $this->json([
                        "error" => true,
                        "message" => "Le nom d'artiste est déjà utilisé. Veuillez choisir un autre nom.",
                    ], 409);
                }
                if (!preg_match("'/^[a-zA-ZÀ-ÿ\-]+$/'", $request->get('fullname'))) {
                    return $this->sendError400();
                }
                $artist->setFullname($request->get('fullname'));
                $this->entityManager->persist($artist);
            }
            if ($request->get("description")) {
                if (!preg_match("'/^[a-zA-ZÀ-ÿ\-]+$/'", $request->get('description'))) {
                    return $this->sendError400();
                }

                $artist->setDescription($request->get('description'));
                $this->entityManager->persist($artist);
            }

            if ($request->get('id_label')) {
                $Label = $this->entityManager->getRepository(Label::class)->findOneBy(['id_label' => $request->get('id_label')]);
                if (!$Label) {
                    return $this->sendError400();
                }
                $oldLabel = $this->entityManager->getRepository(ArtistHasLabel::class)->findOneBy(['id_User' => $artist->getUserIdUser(), 'quittedAt' => null]);
                $oldLabel->setQuittedAt(new DateTime());
                $this->entityManager->persist($oldLabel);

                $newLabelOfArtist = new ArtistHasLabel();
                $newLabelOfArtist->setIdLabel($request->get('id_label'));
                $newLabelOfArtist->setIdArtist($artist->getId());
                $newLabelOfArtist->setAddedAt(new \DateTimeImmutable());

                $this->entityManager->persist($newLabelOfArtist);
            }

            $this->entityManager->flush();
            return $this->json([
                "succes" => false,
                "message" => "Les informations de l'artiste ont été mises à jour avec succès."
            ], 200);

        } else {
            parse_str($request->getContent(), $artistData);

            $this->verifyKeys($artistData) == true ? true : $this->sendError400(4);
            if (empty($artistData["label"]) || empty($artistData["fullname"])) {
                return $this->sendError400(2);
            }

            //verification du format de id_label 
            if (!preg_match($regex_idLabel, $artistData["label"])) {
                return $this->sendError400(3);
            }
            if(!preg_match('^/[\p{P}\a-zA-ZÀ-ÿ0-9\p{S}\µ]+$/^', $artistData["fullname"]))
            $currentDate = new DateTime();
            $age = $currentDate->diff($currentUser->getBirthday());
            if (($age->y) < 16) {
                return $this->json([
                    'error' => true,
                    'message' => "Vous devez avoir au moins 16 ans pour être artiste."

                ], 403);
            }
            /*$artist = $this->entityManager->getRepository(Artist::class)->findOneBySomeField($currentUser->getId());
            if ($artist != null) {
                return $this->json([
                    'error' => true,
                    'message' => 'L\'utilisateur ne peut créer qu\'un seul compte.Veuillez supprimer le compte existatnt pour créer un nouveau'

                ], 403);
            }*/
            $artistFullname = $this->entityManager->getRepository(Artist::class)->GetExiteFullname($artistData["fullname"]);
            if ($artistFullname[1] != 0) {
                return $this->json([
                    'error' => true,
                    'message' => 'Ce nom d\'artist est déjà pris. Veuillez en choisir un autre.'
                ], 409);
            }
            $explodeData = explode(",", $artistData['avatar']);
            if (count($explodeData) == 2) {
                # Verify File Extension
                $reexplodeData = explode(";", $explodeData[0]);
                $fileExt = explode("/", $reexplodeData[0]);

                $fileExt[1] == "png" ? "png" : ($fileExt[1] == "jpeg" ? "jpeg" : $this->sendError422(2));

                $base64IsValid = base64_decode($explodeData[1], true);
                # Check if Base64 string can be decoded
                if ($base64IsValid === false){
                    return $this->sendError422(1);
                }
                $file = base64_decode($explodeData[1]);

                # Check if file size is correct
                $fileSize = ((strlen($file) * 0.75)/ 1024)/1024;
                if (number_format($fileSize, 1) < 1.0 || number_format($fileSize, 1) >= 8.0){
                    return $this->sendError422(3);
                }

                $chemin = $this->getParameter('upload_directory') . '/' . $artistData["fullname"];
                mkdir($chemin);
                file_put_contents($chemin . '/avatar.'+ $fileExt[1], $file);
            }

            $newArtist = new Artist();
            $newArtist->setFullname($artistData["fullname"]);
            $newArtist->setUserIdUser($currentUser);


            $this->entityManager->persist($newArtist);
            $this->entityManager->flush();
            $artistId = $this->entityManager->getRepository(Artist::class)->findOneBySomeField($currentUser->getId());

            $labelOfArtist = new ArtistHasLabel();
            $labelOfArtist->setIdLabel($artistData["label"]);
            $labelOfArtist->setIdArtist($artistId);
            $labelOfArtist->setAddedAt(new \DateTimeImmutable());
            $this->entityManager->persist($labelOfArtist);
            $this->entityManager->flush();

            return $this->json([
                "success" => true,
                'message' => "Votre compte d'artiste a été créé avec succès. Bienvenue dans notre communauté d'artiste!",
                'artist_id' => $artistId->getId(), // Supposant que l'ID de l'artiste est 1, ajustez selon la logique appropriée
            ], 201); // Utilisez 200 pour indiquer le succès
        }
    }

    private function verifyKeys($requestBody){
        $obligatoryKeys = ['label', 'fullname'];
        $allowedKeys = ['description', 'avatar'];
        $keys = array_keys($requestBody);
        $resultGood = 0;
        foreach($keys as $key){
            if (in_array($key, $obligatoryKeys)){
                $resultGood++;
            } elseif (in_array($key, $allowedKeys)){
                $resultGood++;
            } else {
                $resultGood = 0;
            }
        }
        if ($resultGood < 2){
            return false;
        }
        return true;
    }

    private function sendError400(int $errorCode)
    {
        switch($errorCode){
            case 1:
                return $this->json([
                    "error" => true,
                    "message" => "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.",
                ], 400);
            case 2:
                return $this->json([
                    "error"=> true,
                    "message"=>"L'id du label et le fullname sont obligatoires"
                ], 400);
            case 3:
                return $this->json([
                    'error' => true,
                    'message' => 'Le format de l\'id du label est invalide.',
                ], 400);
            case 4:
                return $this->json([
                    'error' => true,
                    'message' => 'Les données fournies sont invalides ou incomplètes',
                ], 400);

        }
        
    }
    
    private function sendError422(int $errorCode){
        switch($errorCode){
            case 1:
                return $this->json([
                    "error"=>true,
                    "message"=>"Le serveur ne peut pas décoder le contenu base64 en fichier binaire.",
                ], 422);
            case 2:
                return $this->json([
                    "error"=>true,
                    "message"=> "Erreur sur le format du fichier qui n'est pas pris en compte.",
                ], 422);
            case 3:
                return $this->json([
                    "error"=>true,
                    "message"=>"Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.",
                ], 422);
        }
    }

}
