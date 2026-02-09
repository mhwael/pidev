<?php

namespace App\Controller;

use App\Entity\SujetsForum;
use App\Form\SujetsForumType;
use App\Repository\SujetsForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sujets/forum')]
final class SujetsForumController extends AbstractController
{
    private const MIN_LENGTH = 3;

    private function validateSujetsForumForm($form): bool
    {
        $valid = true;
        $titre = $form->get('titre')->getData();
        if ($titre === null || trim((string) $titre) === '') {
            $form->get('titre')->addError(new FormError('Le titre est obligatoire.'));
            $valid = false;
        } elseif (strlen(trim((string) $titre)) < self::MIN_LENGTH) {
            $form->get('titre')->addError(new FormError('Le titre doit contenir au moins ' . self::MIN_LENGTH . ' caractères.'));
            $valid = false;
        }
        $creePar = $form->get('cree_par')->getData();
        if ($creePar === null || trim((string) $creePar) === '') {
            $form->get('cree_par')->addError(new FormError('Le champ "Créé par" est obligatoire.'));
            $valid = false;
        } elseif (strlen(trim((string) $creePar)) < self::MIN_LENGTH) {
            $form->get('cree_par')->addError(new FormError('Le champ "Créé par" doit contenir au moins ' . self::MIN_LENGTH . ' caractères.'));
            $valid = false;
        }
        $categorie = $form->get('categorie')->getData();
        if ($categorie === null || trim((string) $categorie) === '') {
            $form->get('categorie')->addError(new FormError('La catégorie est obligatoire.'));
            $valid = false;
        } elseif (strlen(trim((string) $categorie)) < self::MIN_LENGTH) {
            $form->get('categorie')->addError(new FormError('La catégorie doit contenir au moins ' . self::MIN_LENGTH . ' caractères.'));
            $valid = false;
        }
        if ($form->get('date_creation')->getData() === null) {
            $form->get('date_creation')->addError(new FormError('La date de création est obligatoire.'));
            $valid = false;
        }
        return $valid;
    }
    #[Route(name: 'app_sujets_forum_index', methods: ['GET'])]
    public function index(Request $request, SujetsForumRepository $sujetsForumRepository): Response
    {
        $search = $request->query->get('search');
        $categorie = $request->query->get('categorie');
        $sort = $request->query->get('sort', 'date_creation');
        $order = strtolower($request->query->get('order', 'desc'));
        if (!\in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }
        $sujets_forums = $sujetsForumRepository->searchFilterSort($search, $categorie, $sort, $order);
        $categories = \App\Form\SujetsForumType::CATEGORIES;

        return $this->render('sujets_forum/index.html.twig', [
            'sujets_forums' => $sujets_forums,
            'categories' => $categories,
            'search' => $search,
            'categorie_filter' => $categorie,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'app_sujets_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sujetsForum = new SujetsForum();
        $form = $this->createForm(SujetsForumType::class, $sujetsForum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->validateSujetsForumForm($form)) {
            $entityManager->persist($sujetsForum);
            $entityManager->flush();

            return $this->redirectToRoute('app_sujets_forum_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sujets_forum/new.html.twig', [
            'sujets_forum' => $sujetsForum,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sujets_forum_show', methods: ['GET'])]
    public function show(SujetsForum $sujetsForum): Response
    {
        return $this->render('sujets_forum/show.html.twig', [
            'sujets_forum' => $sujetsForum,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sujets_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SujetsForum $sujetsForum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SujetsForumType::class, $sujetsForum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->validateSujetsForumForm($form)) {
            $entityManager->flush();

            return $this->redirectToRoute('app_sujets_forum_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sujets_forum/edit.html.twig', [
            'sujets_forum' => $sujetsForum,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sujets_forum_delete', methods: ['POST'])]
    public function delete(Request $request, SujetsForum $sujetsForum, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sujetsForum->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($sujetsForum);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sujets_forum_index', [], Response::HTTP_SEE_OTHER);
    }
}
