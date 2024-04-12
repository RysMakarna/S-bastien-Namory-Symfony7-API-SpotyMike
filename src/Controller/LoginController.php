<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class LoginController extends AbstractController
{
    private $repository;
    private $repositoryArtist;
    private $JWTManager;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager)
    {
        $this->JWTManager = $jwtManager;
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->repositoryArtist = $entityManager->getRepository(Artist::class);

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
            if ($user->getActif() == 1) {
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
                //$this->repository->findOneBySomeField($id);
                return $this->json([
                    'error' => false,
                    'message'=>'l\'utilisateur à été authentifié avec succès',
                    'user' => [
                        $user->UserSeriaLogin()
                    ],
                // Assurez-vous que la méthode serialize() retourne les données au format attendu.  
                    'token' => $this->JWTManager->create($user),
                ], 200);

            } else {
                return $this->json([
                    'error' => true,
                    'message' => 'le compte n\'est plus actif  ou suspendu'
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

    #[Route('/register', name: 'app_add_user', methods: ['POST'])]
  
    public function AddUser(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $email = $request->get('email');
        $existingUser = $this->repository->findOneBy(['email' => $email]);

        $dateBirth = \DateTime::createFromFormat('d/m/Y', $request->get('dateBirth'));
        if ($dateBirth){
            $DiG = $dateBirth->format('d/m/Y') === $request->get('dateBirth'); // DiG means Date is Good
        }

        
        //dd($request->get('password'));
        if ($existingUser) {
            return $this->json([
                "message" => 'Cet email est déjà utilisé par un autre compte',
            ], 409);
        }
        if (!$request->get('email') || !$request->get('password') || !$request->get('firstname') || 
            !$request->get('lastname') || !$DiG) {
            return $this->sendErrorMessage400(4);
        }
        if (!preg_match('^\S+@\S+\.\S+$^', $request->get('email'))) {
            return $this->sendErrorMessage400(5);
        }
        if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,20}$/', $request->get('password'))){
            return $this->sendErrorMessage400(6);
        }
        if (!$dateBirth){
            return $this->sendErrorMessage400(7);
        }
        if ($request->get('tel')){
            if(!preg_match('^0[1-7][0-9]{8}$^', $request->get('tel'))){ //Find why need '' on POSTMAN
                return $this->sendErrorMessage400(9);
            }
        }
        if ($request->get('sexe')){
            $sexe = $request->get('sexe') === '0' ? 0 : ($request->get('sexe') === '1' ? 1 : ($request->get('sexe') === '2' ? 2 : null));
            if ($sexe === null) {
                return $this->sendErrorMessage400(10);
            }
        }
        

        #Check if User is 12+ YO
        $currentDate = new \DateTime();
        if ($dateBirth->diff($currentDate)->y < 12){
            return $this->json([
                'error' => true,
                'message' =>"L'Âge de l'utilisateur ne permet de s'inscrire.",
                'diff'=>$dateBirth->diff($currentDate)->y,
            ], 406);
        }
                $user = new User();
                # ID
                $user->setIdUser("User_".rand(0,999)); // Will be Modified. Logic to not have twice or more the same ID.
                # Add Obligatory Values
                $user->setEmail($email);
                $user->setFirstname($request->get('firstname'));
                $user->setLastname($request->get('lastname'));
                $birthday = $dateBirth;
                $user->setBirthday($birthday);
                # Verify Sex and Tel
                if ($sexe){
                    $user->setSexe($sexe);
                }
                if ($request->get('tel')){            
                    $tel = $request->get('tel') ? $request->get('tel') : NULL;
                    $user->setTel($tel);
                }
                # Encrypt and Save Password
                //$encrypte = password_hash($request->get('encrypte'), PASSWORD_DEFAULT);
                $password = $request->get('password');

                $hash = $passwordHash->hashPassword($user, $password);
                $user->setPassword($hash);

                # Create and Update Time
                $user->setCreateAt(new \DateTimeImmutable());
                $user->setUpdateAt(new \DateTime());

                #Save and Send to db
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $this->json([
                    'error' => false,
                    'message' => "L'utilisateur a bien été créé avec succès.",
                    'user' => $user->UserSerialRegis(),
                ], 201);
    }

    private function sendErrorMessage400(int $codeMessage){
        switch($codeMessage) {
            case 4:
                return $this->json([
                    "error"=>true,
                    "message"=>'Des champs obligatoires sont manquants',
                    ],400);
            case 5:
                return $this->json([
                    "error"=>true,
                    "message"=>'Le format de l\'email est invalide',
                    ],400);
            case 6:
                return $this->json([
                    "error"=>true,
                    "message"=>'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère sépcial et avoir 8 caractères minimum',
                    ],400);
            case 7:
                return $this->json([
                    "error"=>true,
                    "message"=>'Le format de la date de naissance est invalide. Le format attendu est JJ/MM/AAAA',
                    ],400);
            case 8:
                return $this->json([
                    "error"=>true,
                    "message"=>'L\'utilisateur doit avoir au moins 12 ans',
                    ],400);
            case 9:
                return $this->json([
                    "error"=>true,
                    "message"=>'Le format du numéro de téléphone est invalide',
                    ],400);
            case 10:
                return $this->json([
                    "error"=>true,
                    "message"=>'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme, 2 pour Non-Binaire',
                    ],400);
            default:

        }
    }
}


