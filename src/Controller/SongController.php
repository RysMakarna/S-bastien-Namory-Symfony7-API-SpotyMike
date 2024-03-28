<?php

namespace App\Controller;

use App\Entity\Song;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class SongController extends AbstractController
{

    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Song::class);
    }

    #[Route('/add/song', name: 'app_song',methods: ['POST'])]
    public function AddSong( Request $request): JsonResponse
    {
        $title = $request->get('title');
        $url = $request->get('url');
        $reponse =$this->repository->findOneBy(['title'=> $title,'url'=> $url]);
        dump($reponse);
        if($reponse){
            return $this->json([
                "message" => 'L utilisateur existe',
            ]);
        }
        $song= new Song();
        $song->setTitle($request->get('title'));
        $song->setUrl($request->get('url'));
        $song->setCreatedAt(new \DateTimeImmutable());
        $song->setVisibility($request->get('visibility'));
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }
}
