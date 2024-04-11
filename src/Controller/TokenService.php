<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

use function Symfony\Component\Clock\now;

class TokenService
{
    private $jwtManager;
    private $jwtProvider;
    private $userRepository;

    public function __construct(JWTTokenManagerInterface $jwtManager, JWSProviderInterface $jwtProvider, UserRepository $userRepository)
    {
        $this->jwtManager = $jwtManager;
        $this->jwtProvider = $jwtProvider;
        $this->userRepository = $userRepository;
    }
    public function checkToken(Request $request)
    {
        if ($request->headers->has('Authorization')) {
            $data = explode(" ", $request->headers->get('Authorization'));
            if (count($data) == 2) {
                $token = $data[1];
                try {
                    $dataToken = $this->jwtProvider->load($token);
                    if ($dataToken->isVerified($token)) {
                        $user = $this->userRepository->findOneBy(["email" => $dataToken->getPayload()["username"]]);
                        return ($user) ? $user : false;
                    }
                } catch (\Throwable $th) {
                    return false;
                }
            }
        } else {
            return true;
        }
        return false;
    }
    public function isExpiredToken(string $token){
        $dataToken = $this->jwtProvider->load($token);
        $expiration = $dataToken->getPayload()['exp'];
        $expirationDate = new \DateTime("@$expiration");
        $now = new \DateTime();
        $user = $this->userRepository->findOneBy(["email" => $dataToken->getPayload()["email"]]);
        return ($expirationDate->diff($now)->i===0)?true:$user;
       //return ($dateNow> $expiration) ? 'Token espiré' :$user;

    }
    public function generateToken(string $email, int $exp){
        return $this->jwtProvider->create(["email" => $email, "exp" => $exp])->getToken();
    }
    public function sendJsonErrorToken(): array
    {
        return [
            'error' => true,
            'message' => "Authentification requise. Vous devez être connecté pour effectuer cette action.",
        ];
    }
}




