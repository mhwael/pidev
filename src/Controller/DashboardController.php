<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(Request $request): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $htmlPath = $projectDir . '/public/back_template/index.html';

        if (!is_file($htmlPath)) {
            throw $this->createNotFoundException('Back template not found.');
        }

        $html = file_get_contents($htmlPath);
        $baseTag = '<base href="' . $request->getBasePath() . '/back_template/">';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n    " . $baseTag, $html, 1);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
