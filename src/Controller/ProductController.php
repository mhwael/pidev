<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductForecastRepository;
use App\Repository\ProductRepository;
use App\Service\MlApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/products')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        ProductForecastRepository $forecastRepo
    ): Response {
        $sort = $request->query->get('sort');
        $dir  = $request->query->get('dir');

        $allowedSort = ['price', 'orders'];
        $sortFinal = in_array($sort, $allowedSort, true) ? $sort : 'default';
        $dirFinal  = in_array(strtolower((string)$dir), ['asc', 'desc'], true) ? strtolower((string)$dir) : 'desc';

        $productsWithCounts = $productRepository->findProductsWithOrdersCount(200, $sortFinal, $dirFinal);
        $stockStats = $productRepository->getStockStats(5);
        $categoryCounts = $productRepository->getCategoryCounts();

        $total = 0;
        foreach ($categoryCounts as $c) {
            $total += (int)$c['productCount'];
        }

        $categoryChart = [];
        foreach ($categoryCounts as $c) {
            $count = (int)$c['productCount'];
            $pct = ($total > 0) ? round(($count * 100) / $total, 1) : 0;
            $categoryChart[] = [
                'category' => $c['category'],
                'productCount' => $count,
                'percentage' => $pct,
            ];
        }

        // ✅ Forecasts are still read from DB (filled by ML API refresh)
        $productIds = [];
        foreach ($productsWithCounts as $row) {
            /** @var Product $p */
            $p = $row[0];
            $productIds[] = $p->getId();
        }

        $forecastMap = $forecastRepo->findLatestByProductIds($productIds, 7);

        return $this->render('product/index.html.twig', [
            'productsWithCounts' => $productsWithCounts,
            'stockStats' => $stockStats,
            'categoryCounts' => $categoryCounts,
            'categoryChart' => $categoryChart,
            'categoryTotal' => $total,
            'sort' => $sortFinal,
            'dir' => $dirFinal,
            'hasSort' => ($sort !== null || $dir !== null),

            // ✅ ML output
            'forecastMap' => $forecastMap,
        ]);
    }

    // ✅ NEW: Refresh AI from website (calls FastAPI refresh endpoints)
    #[Route('/ml/refresh', name: 'product_ml_refresh', methods: ['POST'])]
    public function refreshMl(Request $request, MlApiClient $ml): Response
    {
        // CSRF check (recommended)
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('ml_refresh', $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('product_index');
        }

        try {
            $reco = $ml->refreshRecommendations(6);
            $forecast = $ml->refreshForecasts(7);

            $msg = "✅ AI refreshed. Reco products: " . ($reco['updated_products'] ?? '-') .
                   " | Forecast updated: " . ($forecast['updated'] ?? '-');

            // Optional: show metric for validation
            if (isset($forecast['mae_model'], $forecast['mae_baseline'])) {
                $msg .= " | MAE(model)=" . round((float)$forecast['mae_model'], 4) .
                        " MAE(baseline)=" . round((float)$forecast['mae_baseline'], 4);
            }

            $this->addFlash('success', $msg);

        } catch (\Throwable $e) {
            $this->addFlash('error', 'AI refresh failed: ' . $e->getMessage());
        }

        return $this->redirectToRoute('product_index');
    }

    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            $imageUrl  = trim((string) $product->getImage());

            if (!$imageFile && $imageUrl === '') {
                $this->addFlash('error', 'Please provide an image URL or upload an image.');
                return $this->render('product/new.html.twig', [
                    'product' => $product,
                    'form' => $form->createView(),
                ]);
            }

            if ($imageFile) {
                $ext = strtolower((string) $imageFile->getClientOriginalExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $this->addFlash('error', 'Only JPG, JPEG, PNG, or WEBP images are allowed.');
                    return $this->render('product/new.html.twig', [
                        'product' => $product,
                        'form' => $form->createView(),
                    ]);
                }

                $newName = uniqid('p_', true) . '.' . $ext;
                $imageFile->move($this->getParameter('product_images_dir'), $newName);
                $product->setImage($newName);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created. (Tip: click "Refresh AI" to update predictions/recommendations)');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            $imageUrl  = trim((string) $product->getImage());

            if (!$imageFile && $imageUrl === '') {
                $this->addFlash('error', 'Please keep an image URL or upload a new image.');
                return $this->render('product/edit.html.twig', [
                    'product' => $product,
                    'form' => $form->createView(),
                ]);
            }

            if ($imageFile) {
                $ext = strtolower((string) $imageFile->getClientOriginalExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $this->addFlash('error', 'Only JPG, JPEG, PNG, or WEBP images are allowed.');
                    return $this->render('product/edit.html.twig', [
                        'product' => $product,
                        'form' => $form->createView(),
                    ]);
                }

                $newName = uniqid('p_', true) . '.' . $ext;
                $imageFile->move($this->getParameter('product_images_dir'), $newName);
                $product->setImage($newName);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Product updated. (Tip: click "Refresh AI" to update predictions/recommendations)');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('product_index');
        }

        // ✅ IMPORTANT: prevent deletion if referenced by order_item (FK constraint)
        $conn = $entityManager->getConnection();
        $count = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM order_item WHERE product_id = ?',
            [$product->getId()]
        );

        if ($count > 0) {
            $this->addFlash('error', "Can't delete this product: it is used in $count order item(s). You can set stock=0 instead.");
            return $this->redirectToRoute('product_index');
        }

        // ✅ detach ManyToMany orders (order_product pivot) to avoid pivot constraint
        foreach ($product->getOrders() as $order) {
            $order->removeProduct($product);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('success', 'Product deleted.');
        return $this->redirectToRoute('product_index');
    }
}