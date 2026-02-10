<?php

namespace App\Controller;

use App\Form\SujetsForumType;
use App\Repository\SujetsForumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CardsController extends AbstractController
{
    #[Route('/cards', name: 'cards_forum', methods: ['GET'])]
    public function cards(Request $request, SujetsForumRepository $sujetsForumRepository): Response
    {
        $search = $request->query->get('search');
        $categorie = $request->query->get('categorie');
        $sort = $request->query->get('sort', 'date_creation');
        $order = strtolower((string) $request->query->get('order', 'desc'));

        if (!\in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }

        $sujets_forums = $sujetsForumRepository->searchFilterSort($search, $categorie, $sort, $order);
        $categories = SujetsForumType::CATEGORIES;

        return $this->render('sujets_forum/lesAffiche.html.twig', [
            'sujets_forums' => $sujets_forums,
            'categories' => $categories,
            'search' => $search,
            'categorie_filter' => $categorie,
            'sort' => $sort,
            'order' => $order,
        ]);
    }
}
