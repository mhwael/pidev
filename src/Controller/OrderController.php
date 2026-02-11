<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/orders', name: 'orders_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $orders = $em->getRepository(Order::class)->findBy([], ['id' => 'DESC']);

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/orders/new', name: 'orders_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $order = new Order();

        if ($order->getCreatedAt() === null) {
            $order->setCreatedAt(new \DateTimeImmutable());
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // IMPORTANT: your Order entity requires at least 1 product
            if ($order->getProducts()->count() < 1) {
                $this->addFlash('danger', 'You must select at least 1 product.');
                return $this->render('orders/new.html.twig', [
                    'order' => $order,
                    'form' => $form->createView(),
                ]);
            }

            $conn = $em->getConnection();
            $conn->beginTransaction();

            try {
                // For each product in the order: lock + check stock + decrement
                foreach ($order->getProducts() as $p) {
                    /** @var Product $p */

                    // Re-fetch with a DB lock (prevents race conditions)
                    $lockedProduct = $em->find(Product::class, $p->getId(), LockMode::PESSIMISTIC_WRITE);
                    if (!$lockedProduct) {
                        throw new \RuntimeException('Product not found.');
                    }

                    $lockedProduct->decreaseStock(1);
                }

                $em->persist($order);
                $em->flush();
                $conn->commit();

                $this->addFlash('success', 'Order created and stock updated.');
                return $this->redirectToRoute('orders_index');

            } catch (\Throwable $e) {
                $conn->rollBack();
                $this->addFlash('danger', $e->getMessage());

                return $this->render('orders/new.html.twig', [
                    'order' => $order,
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('orders/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/orders/{id}/edit', name: 'orders_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // NOTE: we do NOT change stock on edit (otherwise it gets complicated)
            $em->flush();
            return $this->redirectToRoute('orders_index');
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/orders/{id}', name: 'orders_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_order_' . $order->getId(), $request->request->get('_token'))) {
            // NOTE: deleting order does NOT restore stock (tell me if you want that behavior)
            $em->remove($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders_index');
    }
}
