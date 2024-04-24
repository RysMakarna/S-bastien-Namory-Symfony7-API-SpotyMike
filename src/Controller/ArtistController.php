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
    #[Route('/artist', name: 'artist_get', methods: 'GET')]
    public function read(Request $request): JsonResponse
    {
        $currentUser = $this->tokenVerifier->checkToken($request,null);
        if (gettype($currentUser) == 'boolean') {
            return $this->tokenVerifier->sendJsonErrorToken();
        }
        $currentPage = $request->get('currentPage');
        if(!is_numeric($currentPage) || $currentPage < 0) {
            return $this->json([
                'error'=>true,
                'message'=>"Le paramètre de pagination est invalide.Veuillez fournir un numéro de page valide."
            ],400);
        }
        $serializedArtists = [];
        $page = $request->query->getInt('page', $currentPage);
        $limit = $request->query->getInt('limit', 5);
        $totalArtist = $this->entityManager->getRepository(Artist::class)->countArtist();
        $totalPages = ceil($totalArtist/$limit);
        $allArtists = $this->entityManager->getRepository(Artist::class)->findAllWithPagination($page, $limit); // tous les informations de l'artiste..
        if($currentPage > $totalPages) {
            return $this->json([
                'error'=>true,
                'message'=> "Aucun artiste trouvé pour la page demandée."
            ],404);
        }
        //dd($allArtists);
        $serializedArtists = [];
        //dd($allArtists);
        foreach ($allArtists as $artist) {
            for( $i = 0; $i < count($artist)-1; $i++ ){
                //dd($artist);
                array_push($serializedArtists, $artist[$i]->ArtistSerealizer($artist["name"]));
            }
        }
        return $this->json([
            'error' => false,
            'artist' => $serializedArtists,
            'message'=>'Informations des artistes récupérées avec succès',
            'pagination'=>[
                'currentPage'=>$page,
                'totalPages'=>$totalPages,
                'totalArtists'=> $totalArtist,
            ],
        ], 200);
        
    }

    #[Route('/artist', name: 'app_artist', methods: 'POST')]
    public function readOne(Request $request): JsonResponse
    {
        $regex_idLabel = '/^12[0-9][a-zA-Z]$/';
        $currentUser = $this->tokenVerifier->checkToken($request,null);
        $urepository = $this->entityManager->getRepository(Artist::class);
        if (gettype($currentUser) == 'boolean') {
            return $this->tokenVerifier->sendJsonErrorToken();
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
            if (empty($request->get('id_label')) || empty($request->get('fullname'))) {
                return $this->json([
                    'error' => true,
                    'message' => 'l\'id du label et le fullname sont obligatoires.',
                ], 400);
            }

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

            $labelOfArtist = new ArtistHasLabel();
            $labelOfArtist->setIdLabel($label);
            $labelOfArtist->setIdArtist($artistId);
            $labelOfArtist->setAddedAt(new \DateTimeImmutable());
            $this->entityManager->persist($labelOfArtist);
            $this->entityManager->flush();

            return $this->json([
                'error' => false,
                'message' => 'Votre compte artiste a été créé avec succès. Bienvenue dans notre communauté d\'artiste!',
                'artist_id' => $artistId->getId(), // Supposant que l'ID de l'artiste est 1, ajustez selon la logique appropriée
            ], 200); // Utilisez 200 pour indiquer le succès
        }
    }

    private function sendError400()
    {
        return $this->json([
            "error" => true,
            "message" => "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.",
        ], 400);
    }

}
