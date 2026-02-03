<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    use AuthButtonsTrait;

    /**
     * Logout route. The Security firewall intercepts this path and performs logout;
     * this method should not be reached.
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(): never
    {
        throw new \LogicException('Logout is handled by the Security firewall.');
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $htmlPath = $projectDir . '/public/front_template/login.html';

        if (!is_file($htmlPath)) {
            throw $this->createNotFoundException('Login template not found.');
        }

        $html = file_get_contents($htmlPath);
        $baseTag = '<base href="' . $request->getBasePath() . '/front_template/">';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n    " . $baseTag, $html, 1);
        $html = str_replace('<!-- AUTH_BUTTONS -->', $this->getAuthButtonsHtml(), $html);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $firstName = trim((string) $request->request->get('firstName', ''));
            $lastName = trim((string) $request->request->get('lastName', ''));
            $email = trim((string) $request->request->get('email', ''));
            $phone = trim((string) $request->request->get('phone', ''));
            $address = trim((string) $request->request->get('address', ''));
            $password = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');

            if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
                return $this->redirectToRoute('app_register', ['error' => 'missing']);
            }
            if ($password !== $passwordConfirm) {
                return $this->redirectToRoute('app_register', ['error' => 'password_mismatch']);
            }

            $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing !== null) {
                return $this->redirectToRoute('app_register', ['error' => 'email_exists']);
            }

            $user = new User();
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail($email);
            $user->setPhone($phone !== '' ? $phone : null);
            $user->setAddress($address !== '' ? $address : null);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login', ['registered' => '1']);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $htmlPath = $projectDir . '/public/front_template/register.html';

        if (!is_file($htmlPath)) {
            throw $this->createNotFoundException('Register template not found.');
        }

        $html = file_get_contents($htmlPath);
        $baseTag = '<base href="' . $request->getBasePath() . '/front_template/">';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n    " . $baseTag, $html, 1);
        $html = str_replace('<!-- AUTH_BUTTONS -->', $this->getAuthButtonsHtml(), $html);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
