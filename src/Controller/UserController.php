<?php

namespace App\Controller;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class UserController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $format;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
        $this->format = 'd-m-Y';
        
    }

    #[Route('/register', name: 'app_add_user', methods: ['POST'])]
  
    public function AddUser(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $email = $request->get('email');
        $existingUser = $this->repository->findOneBy(['email' => $email]);

        $Date = \DateTime::createFromFormat($this->format, $request->get('birthday'));
        
        $DiG = $Date->format($this->format) === $request->get('birthday'); // DiG means Date is Good

        if ($existingUser) {
            return $this->json([
                "message" => 'Un compte utilisant cette adresse est déjà enregistré',
            ], 409);
        }
        if (!$request->get('email') || !$request->get('password') || !$request->get('firstname') || 
            !$request->get('lastname') || !$request->get('birthday') ) {
            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs données obligatoires sont manquantes'
            ], 400);
        }

        if (!preg_match('/^\\S+@\\S+\\.\\S+$/', $request->get('email')) || $DiG == false ||
        !preg_match('/0[1-9][0-9]{8}$/', $request->get('tel'))) {
            return $this->json([
                "message" => "Une ou plusieurs données sont érronées."
            ], 400);
        } // Will need to be corrected


        #Check if User is 12+ YO
        $currentDate = new \DateTime();
        if ($Date->diff($currentDate)->y < 12){
            return $this->json([
                'error' => true,
                'message' =>"L'Âge de l'utilisateur ne permet de s'inscrire.",
            ], 406);
        }
                $user = new User();
                # ID
                $user->setIdUser("User_".rand(0,999)); // Will be Modified. Logic to not have twice or more the same ID.
                # Add Obligatory Values
                $user->setEmail($request->get('email'));
                $user->setFirstname($request->get('firstname'));
                $user->setLastname($request->get('lastname'));
                $birthday = $Date;
                $user->setBirthday($birthday);
                # Verify Sex and Tel
                $sexe = $request->get('sexe') ? $request->get('sexe') : 'Non Précisé';
                $user->setSexe($sexe);
                $tel = $request->get('tel') ? $request->get('tel') : NULL;
                $user->setTel($tel);
                # Encrypt and Save Password
                //$encrypte = password_hash($request->get('encrypte'), PASSWORD_DEFAULT);
                $password = $request->get('password');

                $hash = $passwordHash->hashPassword($user, $password);
                $user->setPassword($hash);

                # Create and Update Time
                $user->setCreateAt(new \DateTimeImmutable());
                $user->setUpdateAt(new \DateTime());
                $user->setActif(true);

                #Save and Send to db
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $this->json([
                    'error' => false,
                    'message' => "L'utilisateur a bien été créé avec succès.",
                    'user' => $user->UserSerial()
                ], 201);
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
    #[Route('/password-lost', name: 'app_read_user')]
    public function DeleteAcount(Request $request): JsonResponse
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
        //verifier si l'utilisateur à un email
        $current_user = $this->repository->findOneBy(['email'=> $email]);
        if($current_user == null){
            return $this->json([
                'error'=>true,
                'message'=> 'Aucun compte  n\'est associé à cet email.Veuillez  vérifier et réssayer'
            ],404);
        }
        $cache = new FilesystemAdapter();
        $cacheKey = 'reset_password_' . urlencode($email);
        $cacheItem = $cache->getItem($cacheKey);
        $requestCount = $cacheItem->get() ?? 0;
 
 
        $cacheItem->set($requestCount + 1);
        $cacheItem->expiresAfter(5); // 20 seconds
        $cache->save($cacheItem);



        return $this->json([
            'message' => 'Un email de réinitialisation de mot de passe à été envoyé à votre adresse email.
            Veuillez suivre les instructions contenues dans l\'email pour réinitialiser votre mot de passe.',
        ], 200);
    }

    #[Route('/update/user/{id}', name: 'app_update_user', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /*$user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Aucune compte avec ce id à modifier !',
            ], 444);
        }
        $user->setEmail($request->get('email'));
        $user->setTel($request->get('tel'));
        $this->entityManager->flush();*/
        return $this->json([
            'message' => 'modifier avec succès',
        ], 200);
    }
    #[Route('/delete/user/{id}', name: 'app_delete_user', methods: ['delete'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Aucune compte avec ce id à modifier !',
            ], 444);
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utisateur supprimer avec succès!',
        ], 200);
    }

}
