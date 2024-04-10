<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Validator\Constraints\Length;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $repositoryArtist;
    private $JWTManager;

    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $JWTManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->repositoryArtist = $entityManager->getRepository(Artist::class);
        $this->JWTManager = $JWTManager;

    }
    // pointless but need to test something
    #[Route('/login', name: 'app_login', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }

    #[Route('/login', name: 'app_login_post', methods: 'POST')]
    public function login(Request $request, UserPasswordHasherInterface $passwordHash ): JsonResponse
    {
        $email_validation_regex = '/^\\S+@\\S+\\.\\S+$/';
        $password_pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,20}$/';
        $email = $request->get('email');
        $password = $request->get('password');

        //vérifier que les données son vite
        if (empty($email) || empty($password)) {
            return $this->sendErrorMessage408(true);
        }
        //vérifier que le mot de pass
        if (!preg_match($email_validation_regex, $email)) {
            return $this->sendErrorMessage408(false);
        }

        if (!preg_match($password_pattern, $password) || strlen($password)< 8){
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule,une minuscule ,un chiffre,une caractère spécial et avoir 8 caractères minimun '
            ], 400);
        }
        // nombre d'erreur ...
        $user = $this->repository->findOneBy(['email' => $email]);
        if ($user) {
            $currentTime = new \DateTime();
            $UpdateDate = $user->getUpdateAt();
            if ($user->isActif() == 1) {
                if ($user->getnbTentative() >= 5) { // verifie le temps
                    if( $UpdateDate->diff($currentTime)->i < 1){
                        return $this->json([
                            'error' => true,
                            'message' => 'Trop de tentative sur email ' . $user->getEmail() . ' Veuillez patienter 2 minutes'
                        ], 429);
                    }else{
                        $this->ResetNumberTentative($user);
                    }
                }

                if (!$passwordHash->isPasswordValid($user, $password)) {
                    $nbt = $user->getnbTentative() + 1;
                    $user->setnbTentative($nbt);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    return $this->sendErrorMessage408(true);
                }
                $this->ResetNumberTentative($user);
                $id = $user->getId();
                $artist = $this->repositoryArtist->findOneBySomeField($id);
                //$this->repository->findOneBySomeField($id);
                return $this->json([
                    'error' => false,
                    'message'=>'l\'utilisateur à été authentifié avec succès',
                    'user' => [
                        $user->UserSerialRegis( $artist )
                    ],
                // Assurez-vous que la méthode serialize() retourne les données au format attendu.  
                    'token' => $this->JWTManager->create($user),
                ], 200);

            } else {
                return $this->json([
                    'error' => true,
                    'message' => 'le compte n\est plus actif  ou suspendu'
                ], 403);
            }

        }
        return $this->sendErrorMessage408(true);
    }
    public function sendErrorMessage408($error)
    {
        return $this->json([
            'error' => true,
            'message' => ($error) ? 'Email/password manquants' : 'Le format de l \'email est invalide'
        ], 400);
    }
    public function ResetNumberTentative(User $currentUser){
        $currentUser->setnbTentative(0);
        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();
    }
}


