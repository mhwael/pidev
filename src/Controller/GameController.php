<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use App\Service\IGDBService; // ✨ Import your new service
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/game')]
final class GameController extends AbstractController
{
    #[Route(name: 'app_game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        $total = $gameRepository->count([]);
        $ranked = $gameRepository->count(['hasRanking' => true]);
        $unranked = $total - $ranked;

        return $this->render('game/index.html.twig', [
            'games' => $gameRepository->findAll(),
            'stats' => [
                'total' => $total,
                'ranked' => $ranked,
                'unranked' => $unranked
            ]
        ]);
    }

    #[Route('/new', name: 'app_game_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        IGDBService $igdb // ✨ Inject IGDB Service here
    ): Response {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 🚀 AUTOMATION: Fetch official data from IGDB
            $gameName = (string) $game->getName();
            $externalData = $igdb->getGameDetails($gameName);

            // PHPStan Level 8: Null-check before accessing array
            if ($externalData !== null && isset($externalData['summary'])) {
                $game->setDescription((string) $externalData['summary']);
            }

            // HANDLE IMAGE UPLOAD
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('coverImage')->getData();

            if ($imageFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);
                $game->setCoverImage($newFilename);
            }

            $entityManager->persist($game);
            $entityManager->flush();

            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_game_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Game $game, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        IGDBService $igdb // ✨ Inject IGDB Service here too
    ): Response {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // OPTIONAL: Update description if it's currently empty
            if (empty($game->getDescription())) {
                $externalData = $igdb->getGameDetails((string) $game->getName());
                if ($externalData !== null && isset($externalData['summary'])) {
                    $game->setDescription((string) $externalData['summary']);
                }
            }

            $imageFile = $form->get('coverImage')->getData();
            if ($imageFile instanceof UploadedFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);
                $game->setCoverImage($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    private function uploadImage(UploadedFile $imageFile, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $extension = $imageFile->guessExtension() ?? 'bin';
        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

        try {
            /** @var string $uploadDir */
            $uploadDir = $this->getParameter('kernel.project_dir');
            
            $imageFile->move(
                $uploadDir . '/public/uploads/guide',
                $newFilename
            );
        } catch (FileException $e) {
            // Handle exception
        }

        return $newFilename;
    }

    #[Route('/{id}', name: 'app_game_show', methods: ['GET'])]
    public function show(Game $game): Response
    {
        return $this->render('game/show.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/{id}', name: 'app_game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$game->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($game);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
    }
}