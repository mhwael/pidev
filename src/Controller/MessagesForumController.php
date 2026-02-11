<?php

namespace App\Controller;

use App\Entity\MessagesForum;
use App\Form\MessagesForumType;
use App\Repository\MessagesForumRepository;
use App\Repository\SujetsForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/messages/forum')]
final class MessagesForumController extends AbstractController
{
    private const MIN_LENGTH = 3;

    private function validateMessagesForumForm($form): bool
    {
        $valid = true;

        if ($form->get('sujetsForum')->getData() === null) {
            $form->get('sujetsForum')->addError(new FormError('Veuillez sélectionner un sujet.'));
            $valid = false;
        }

        if ($form->get('auteur_id')->getData() === null) {
            $form->get('auteur_id')->addError(new FormError("L'identifiant auteur est obligatoire."));
            $valid = false;
        }

        $contenu = $form->get('contenu')->getData();
        if ($contenu === null || trim((string) $contenu) === '') {
            $form->get('contenu')->addError(new FormError('Le contenu est obligatoire.'));
            $valid = false;
        } elseif (strlen(trim((string) $contenu)) < self::MIN_LENGTH) {
            $form->get('contenu')->addError(new FormError('Le contenu doit contenir au moins ' . self::MIN_LENGTH . ' caractères.'));
            $valid = false;
        }

        if ($form->get('date_creation')->getData() === null) {
            $form->get('date_creation')->addError(new FormError('La date de création est obligatoire.'));
            $valid = false;
        }

        return $valid;
    }

    #[Route(name: 'app_messages_forum_index', methods: ['GET'])]
    public function index(MessagesForumRepository $messagesForumRepository): Response
    {
        return $this->render('messages_forum/index.html.twig', [
            'messages_forums' => $messagesForumRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_messages_forum_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SujetsForumRepository $sujetsForumRepository
    ): Response {
        $messagesForum = new MessagesForum();

        // Pré-sélection du sujet via ?sujet_id=...
        $sujetId = $request->query->getInt('sujet_id');
        if ($sujetId > 0) {
            $sujet = $sujetsForumRepository->find($sujetId);
            if ($sujet !== null) {
                $messagesForum->setSujetsForum($sujet);
            }
        }

        $form = $this->createForm(MessagesForumType::class, $messagesForum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->validateMessagesForumForm($form)) {
            $entityManager->persist($messagesForum);
            $entityManager->flush();

            $sujet = $messagesForum->getSujetsForum();
            if ($sujet !== null) {
                return $this->redirectToRoute('app_sujets_forum_show', ['id' => $sujet->getId()], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_messages_forum_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('messages_forum/new.html.twig', [
            'messages_forum' => $messagesForum,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_messages_forum_show', methods: ['GET'])]
    public function show(MessagesForum $messagesForum): Response
    {
        return $this->render('messages_forum/show.html.twig', [
            'messages_forum' => $messagesForum,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_messages_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MessagesForum $messagesForum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MessagesForumType::class, $messagesForum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->validateMessagesForumForm($form)) {
            $entityManager->flush();

            $sujet = $messagesForum->getSujetsForum();
            if ($sujet !== null) {
                return $this->redirectToRoute('app_sujets_forum_show', ['id' => $sujet->getId()], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_messages_forum_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('messages_forum/edit.html.twig', [
            'messages_forum' => $messagesForum,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_messages_forum_delete', methods: ['POST'])]
    public function delete(Request $request, MessagesForum $messagesForum, EntityManagerInterface $entityManager): Response
    {
        // ✅ récupérer le sujet AVANT suppression
        $sujet = $messagesForum->getSujetsForum();

        if ($this->isCsrfTokenValid('delete' . $messagesForum->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($messagesForum);
            $entityManager->flush();
        }

        // ✅ redirection correcte avec l'id obligatoire
        if ($sujet !== null) {
            return $this->redirectToRoute('app_sujets_forum_show', ['id' => $sujet->getId()], Response::HTTP_SEE_OTHER);
        }

        // fallback si jamais le sujet est null
        return $this->redirectToRoute('app_messages_forum_index', [], Response::HTTP_SEE_OTHER);
    }
}
