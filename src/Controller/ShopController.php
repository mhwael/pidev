<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ShopController extends AbstractController
{
    // ✅ NEW: /shop route so your homepage "Shop" link works
    #[Route('/shop', name: 'shop_home', methods: ['GET'])]
    public function shopHome(): Response
    {
        return $this->redirectToRoute('shop_products');
    }

    #[Route('/shop/products', name: 'shop_products', methods: ['GET'])]
    public function products(Request $request, ProductRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));

        return $this->render('Shop/products.html.twig', [
            'products' => $repo->search($q !== '' ? $q : null, $category !== '' ? $category : null),
            'q' => $q,
            'category' => $category,
        ]);
    }

    #[Route('/shop/products/{id}', name: 'shop_product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showProduct(Product $product): Response
    {
        return $this->render('Shop/product_show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/shop/order/{id}', name: 'shop_order_product', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function orderOne(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $firstName = trim((string) $request->request->get('firstName', ''));
        $lastName  = trim((string) $request->request->get('lastName', ''));
        $phone     = trim((string) $request->request->get('phone', ''));
        $email     = trim((string) $request->request->get('email', ''));

        $order = new Order();
        $order->setReference('ORD-' . date('YmdHis'));
        $order->setStatus('NEW');
        $order->setCreatedAt(new \DateTimeImmutable());

        $order->setCustomerFirstName($firstName);
        $order->setCustomerLastName($lastName);
        $order->setCustomerPhone($phone);
        $order->setCustomerEmail($email);

        $order->addProduct($product);
        $order->setTotalAmount((string) $product->getPrice());

        // ✅ Real validation
        $errors = $validator->validate($order);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('shop_product_show', ['id' => $product->getId()]);
        }

        $em->persist($order);
        $em->flush();

        return $this->redirectToRoute('my_orders');
    }

    #[Route('/shop/my-orders', name: 'my_orders', methods: ['GET'])]
    public function myOrders(EntityManagerInterface $em): Response
    {
        $orders = $em->getRepository(Order::class)->findBy([], ['id' => 'DESC']);

        return $this->render('Shop/my_orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
