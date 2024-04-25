<?php

namespace App\Controller;


use App\Entity\User;
use App\Entity\Artist;
use PhpParser\Builder\Class_;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
class UserController extends AbstractController
{
    private $entityManager;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager, TokenService  $tokenService)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenService;
    }

    #[Route('/read/user', name: 'app_read_user')]
    public function readUser(): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findAll();
        if (count($user) > 0) {
            $usersArray = array_map(function ($user) {
                return $user->UserSerializer(); // Ensure you have a toArray() method in your User entity
            }, $user);

            return $this->json([
                $usersArray,
            ], 200);
        }
        return $this->json([
            'message' => 'aucun utilisateur pour le moment',
        ], 204);

    }
  
    #[Route('/user', name: 'app_update_user', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $currentUser = $this->tokenVerifier->checkToken($request);

        if (gettype($currentUser) == 'boolean') {
            return $this->tokenVerifier->sendJsonErrorToken();
        }
        $repository = $this->entityManager->getRepository(User::class);

        parse_str($request->getContent(), $userData);

        $this->verifyKeys($userData) == true ? true : $this->sendErrorMessage400(3);

        if(preg_match('^/^[a-zA-Z -]+.{1,60}$/^', $userData['firstname']) || strlen($userData['firstname']) >=60 
         || preg_match('^/^[a-zA-Z -]+.{1,60}$/^', $userData['lastname']) || strlen($userData['lastname']) >=60){
            return $this->json([
                "error"=> true,
                "message"=> "Erreur de validation des données.",
            ], 422);
        }
        
        if(!preg_match('^0[1-7][0-9]{8}$^', $userData['tel'])){
            return $this->sendErrorMessage400(1);
        }

        $otherUser = $repository->findOneBy(["tel" => $userData['tel']]);
        if ($otherUser && $currentUser->getEmail() != $otherUser->getEmail()){
          return $this->json([
            'error'=> true,
            "message"=>"Conflit de données. Le numéro est déjà utilisé par un autre utilisateur.",
          ], 409);
        }
        if ($userData['sexe'] !== null){
            $sexe = $userData['sexe'] === '0' ? 0 : ($userData['sexe'] === '1' ? 1 : ($userData['sexe'] === '2' ? 2 : null));
            if ($sexe === null) {
                return $this->sendErrorMessage400(2);
            }
        }

        $currentUser->setFirstname($userData['firstname']);
        $currentUser->setLastname($userData['lastname']);
        $currentUser->setTel($userData['tel']);
        $currentUser->setSexe($sexe);

        $this->entityManager->persist($currentUser);
                $this->entityManager->flush();
                return $this->json([
                    'error' => false,
                    'message' => "Votre inscription a bien été prise en compte.",
                ], 201);
        }
    #[Route('/password-lost', name: 'app_read_user')]
    public function PasswordLost(Request $request,JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $email_validation_regex = '/^\\S+@\\S+\\.\\S+$/';
        $email = $request->get('email');

        if(empty($email)){
            return $this->json([
                'error'=>true,
                'message'=> 'L\email manquant.Veuillez fornir votre mail pour la récupération du mot de passe.'
            ],400);
        }
        
        if(!preg_match($email_validation_regex,$email)){
            return $this->json([
            'error'=>true,
            'message'=> 'Le format de l \'email est invalide.Veuillez entrer un email valide'
            ],400);
        }
        $current_user =$this->entityManager->getRepository(User::class)->findOneBy(['email'=> $email]);
        if($current_user == null){
            return $this->json([
                'error'=>true,
                'message'=> 'Aucun compte  n\'est associé à cet email.Veuillez  vérifier et réssayer'
            ],403);
        }
        $cache = new FilesystemAdapter();
        $cacheKey = 'reset_password_' . urlencode($email);
        $nbTentative = $cache->getItem($cacheKey);
        $allTentative = $nbTentative->get() ?? 0;
 
        $nbTentative->set($allTentative + 1);
        $nbTentative->expiresAfter(300); // 5minutes
        $cache->save($nbTentative);
        
        if($allTentative >= 3){
            return $this->json([
                'message' => 'Trop de demande de réinitialisation de mot de passe.Veuillez attendre avant de réessayer dans 5 minutes',
            ], 429);
        }
        $token = $this->tokenVerifier->generateToken($email,time()+120);//Génération du token 
        return $this->json([
            'success'=>true,
            'message' => 'Un email de réinitialisation de mot de passe à été envoyé à votre adresse email.Veuillez suivre les instructions contenues dans l\'email pour réinitialiser votre mot de passe.',
            'token'=> $token,  
        ], 200);
    }
  
    #[Route('/account-deactivation', name: 'app_delete_user', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {   
        $repository = $this->entityManager->getRepository(Artist::class);
        $currentUser = $this->tokenVerifier->checkToken($request);
        if (gettype($currentUser) == 'boolean') {
            return $this->tokenVerifier->sendJsonErrorToken();
        }

        if($currentUser->getActif() === 0){
            return $this->json([
                "error"=>true,
                "message"=>"Le compte est déjà désactivé.",
            ], 409);
        }

        $currentUser->setActif(0);
        $artist=$repository->findOneBy(["User_idUser" => $currentUser->getIdUser()]);
        if ($artist){
            $artist->setActif(0);
            foreach($artist->getAlbums() as $album){
                $album->setActif(0);
                $this->entityManager->persist($album);
                foreach($album->getSongIdSong() as $song){
                    $song->setActif(0);
                    $this->entityManager->persist($song);
                }
            }
        }

        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();
        return $this->json([
            'success' => true,
            'message' => "Votre compte a été désactivé avec succès. Nous sommes désolé de vous voir partir.",
        ], 200);

    }

    private function verifyKeys($requestBody){
        $allowedKeys = ['firstname', 'lastname', 'tel', 'sexe'];
        $keys = array_keys($requestBody);

        foreach($keys as $key){
            if (!in_array($key, $allowedKeys)){
                return false;
            }
        }
        return true;
    }

    private function sendErrorMessage400(int $errorCode){
        switch($errorCode){
            case 1:
                return $this->json([
                    "error" => true,
                    "message" => "Le format du numéro de téléphone est invalide",
                ], 400);
            case 2:
                return $this->json([
                    "error"=> true,
                    "message"=> "La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme, 2 pour Non-Binaire",
                ], 400);
            case 3:
                return $this->json([
                    "error" => true,
                    "message" => "Les données fournies sont invalides ou incomplètes",
                ], 400);
        } 
    }
}
