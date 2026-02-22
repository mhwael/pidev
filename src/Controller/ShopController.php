<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\CheckoutType;
use App\Repository\ProductRepository;
use App\Service\OrderMailer;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    private const CART_KEY = 'cart_items'; // [productId => qty]

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

        $categories = $repo->getDistinctCategories();
        if ($category !== '' && !in_array($category, $categories, true)) {
            $category = '';
        }

        return $this->render('Shop/products.html.twig', [
            'products' => $repo->search($q !== '' ? $q : null, $category !== '' ? $category : null),
            'q' => $q,
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    #[Route('/shop/products/{id}', name: 'shop_product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showProduct(Product $product): Response
    {
        return $this->render('Shop/product_show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/shop/cart/add/{id}', name: 'shop_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addToCart(Product $product, Request $request, SessionInterface $session): Response
    {
        $qty = (int) $request->request->get('qty', 1);
        $qty = max(1, min(20, $qty));

        $cart = $session->get(self::CART_KEY, []);
        $pid = $product->getId();

        $cart[$pid] = ($cart[$pid] ?? 0) + $qty;

        $session->set(self::CART_KEY, $cart);
        $this->addFlash('success', 'Product added to cart.');

        return $this->redirectToRoute('shop_cart');
    }

    #[Route('/shop/cart', name: 'shop_cart', methods: ['GET'])]
    public function cart(SessionInterface $session, ProductRepository $repo): Response
    {
        $cart = $session->get(self::CART_KEY, []);
        $ids = array_keys($cart);

        $products = $ids
            ? $repo->createQueryBuilder('p')->andWhere('p.id IN (:ids)')->setParameter('ids', $ids)->getQuery()->getResult()
            : [];

        $lines = [];
        $total = 0.0;

        foreach ($products as $p) {
            /** @var Product $p */
            $qty = (int) ($cart[$p->getId()] ?? 0);
            if ($qty < 1) continue;

            $lineTotal = (float) $p->getPrice() * $qty;
            $total += $lineTotal;

            $lines[] = [
                'product' => $p,
                'qty' => $qty,
                'lineTotal' => number_format($lineTotal, 2, '.', ''),
            ];
        }

        return $this->render('Shop/cart.html.twig', [
            'lines' => $lines,
            'total' => number_format($total, 2, '.', ''),
        ]);
    }

    #[Route('/shop/cart/update/{id}', name: 'shop_cart_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateCart(Product $product, Request $request, SessionInterface $session): Response
    {
        $qty = (int) $request->request->get('qty', 1);
        $qty = max(0, min(20, $qty));

        $cart = $session->get(self::CART_KEY, []);
        $pid = $product->getId();

        if ($qty === 0) unset($cart[$pid]);
        else $cart[$pid] = $qty;

        $session->set(self::CART_KEY, $cart);
        return $this->redirectToRoute('shop_cart');
    }

    #[Route('/shop/cart/remove/{id}', name: 'shop_cart_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeFromCart(Product $product, SessionInterface $session): Response
    {
        $cart = $session->get(self::CART_KEY, []);
        unset($cart[$product->getId()]);
        $session->set(self::CART_KEY, $cart);

        return $this->redirectToRoute('shop_cart');
    }

    #[Route('/shop/checkout', name: 'shop_checkout', methods: ['GET','POST'])]
    public function checkout(
        Request $request,
        SessionInterface $session,
        ProductRepository $repo,
        EntityManagerInterface $em,
        OrderMailer $orderMailer
    ): Response {
        $cart = $session->get(self::CART_KEY, []);
        if (!$cart) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('shop_products');
        }

        $ids = array_keys($cart);
        $products = $repo->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        if (!$products) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('shop_products');
        }

        $total = 0.0;
        foreach ($products as $p) {
            $qty = (int) ($cart[$p->getId()] ?? 0);
            if ($qty > 0) $total += (float) $p->getPrice() * $qty;
        }

        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $conn = $em->getConnection();
            $conn->beginTransaction();

            try {
                $order = new Order();
                $order->setReference('ORD-' . date('YmdHis'));
                $order->setStatus('NEW');
                $order->setCreatedAt(new \DateTimeImmutable());

                $order->setCustomerFirstName(trim((string) $data['firstName']));
                $order->setCustomerLastName(trim((string) $data['lastName']));
                $order->setCustomerPhone(trim((string) $data['phone']));
                $order->setCustomerEmail(trim((string) $data['email']));

                $order->setPaymentMethod($data['paymentMethod'] ?? 'COD');
                $order->setPaymentStatus('PENDING');
                $order->setTotalAmount(number_format($total, 2, '.', ''));

                foreach ($products as $p) {
                    $qty = (int) ($cart[$p->getId()] ?? 0);
                    if ($qty < 1) continue;

                    $locked = $em->find(Product::class, $p->getId(), LockMode::PESSIMISTIC_WRITE);
                    if (!$locked) throw new \RuntimeException('Product not found.');

                    if ((int) $locked->getStock() < $qty) {
                        throw new \RuntimeException('Not enough stock for: ' . $locked->getName());
                    }

                    $item = new OrderItem();
                    $item->setProduct($locked);
                    $item->setQuantity($qty);
                    $item->setUnitPrice((string) $locked->getPrice());
                    $order->addItem($item);

                    $locked->decreaseStock($qty);
                    $order->addProduct($locked); // optional old relation
                }

                if ($order->getPaymentMethod() === 'CARD') {
                    $order->setPaymentStatus('PAID');
                    $order->setStatus('PAID');
                } else {
                    $order->setPaymentStatus('PENDING');
                    $order->setStatus('NEW');
                }

                $em->persist($order);
                $em->flush();
                $conn->commit();

                // ✅ Send email + PDF after commit
                try {
                    $orderMailer->sendOrderConfirmation($order);
                    $this->addFlash('success', 'Email sent to: ' . $order->getCustomerEmail());
                } catch (\Throwable $mailErr) {
                    $this->addFlash('error', 'Order placed but email failed: ' . $mailErr->getMessage());
                }

                $session->remove(self::CART_KEY);

                $this->addFlash('success', 'Order placed successfully.');
                return $this->redirectToRoute('my_orders');

            } catch (\Throwable $e) {
                $conn->rollBack();
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('Shop/checkout.html.twig', [
            'form' => $form->createView(),
            'total' => number_format($total, 2, '.', ''),
        ]);
    }

    #[Route('/shop/my-orders', name: 'my_orders', methods: ['GET'])]
    public function myOrders(EntityManagerInterface $em): Response
    {
        $orders = $em->getRepository(Order::class)->findBy([], ['id' => 'DESC']);

        return $this->render('Shop/my_orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/shop/test-mail', name: 'shop_test_mail', methods: ['GET'])]
public function testMail(OrderMailer $orderMailer, EntityManagerInterface $em): Response
{
    $order = $em->getRepository(\App\Entity\Order::class)->findOneBy([], ['id' => 'DESC']);
    if (!$order) {
        return new Response('No order found.');
    }

    try {
        $orderMailer->sendOrderConfirmation($order);
        return new Response('Mail sent to: ' . $order->getCustomerEmail());
    } catch (\Throwable $e) {
        return new Response('MAIL ERROR: ' . $e->getMessage());
    }
}
     #[Route('/debug-mailer-dsn', name: 'debug_mailer_dsn')]
public function debugMailerDsn(): Response
{
    return new Response((string) $_ENV['MAILER_DSN']);
}
}