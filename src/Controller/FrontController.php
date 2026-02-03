<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FrontController extends AbstractController
{
    use AuthButtonsTrait;

    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $e = fn($s) => htmlspecialchars((string) $s, \ENT_QUOTES, 'UTF-8');

        if ($request->isMethod('POST')) {
            $firstName = trim((string) $request->request->get('firstName', ''));
            $lastName = trim((string) $request->request->get('lastName', ''));
            $email = trim((string) $request->request->get('email', ''));
            $phone = trim((string) $request->request->get('phone', ''));
            $address = trim((string) $request->request->get('address', ''));
            $newPassword = $request->request->get('newPassword', '');
            $newPasswordConfirm = $request->request->get('newPasswordConfirm', '');

            if ($firstName !== '' && $lastName !== '' && $email !== '') {
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setPhone($phone !== '' ? $phone : null);
                $user->setAddress($address !== '' ? $address : null);

                if ($email !== $user->getEmail()) {
                    $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                    if ($existing === null) {
                        $user->setEmail($email);
                    }
                }

                if ($newPassword !== '' && $newPassword === $newPasswordConfirm) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                }

                $entityManager->flush();
            }

            return $this->redirectToRoute('app_profile', ['updated' => '1']);
        }

        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $email = $user->getUserIdentifier();
        $phone = $user->getPhone() ?? '';
        $address = $user->getAddress() ?? '';
        $createdAt = $user->getCreatedAt();
        $updated = $request->query->get('updated') === '1';

        $profileContent = '';
        if ($updated) {
            $profileContent .= '<div class="alert alert-success mb-4"><i class="fas fa-check-circle mr-2"></i>Profile updated successfully.</div>';
        }
        $profileContent .= '<form method="post" action="/profile" class="w-100">';
        $profileContent .= '<div class="row"><div class="col-md-6"><div class="form-group mb-3"><label for="firstName"><i class="fas fa-user mr-2"></i>First Name</label><input type="text" class="form-control" id="firstName" name="firstName" value="' . $e($firstName) . '" required></div></div>';
        $profileContent .= '<div class="col-md-6"><div class="form-group mb-3"><label for="lastName"><i class="fas fa-user mr-2"></i>Last Name</label><input type="text" class="form-control" id="lastName" name="lastName" value="' . $e($lastName) . '" required></div></div></div>';
        $profileContent .= '<div class="form-group mb-3"><label for="email"><i class="fas fa-envelope mr-2"></i>Email</label><input type="email" class="form-control" id="email" name="email" value="' . $e($email) . '" required></div>';
        $profileContent .= '<div class="form-group mb-3"><label for="phone"><i class="fas fa-phone mr-2"></i>Phone</label><input type="tel" class="form-control" id="phone" name="phone" value="' . $e($phone) . '" placeholder="Optional"></div>';
        $profileContent .= '<div class="form-group mb-3"><label for="address"><i class="fas fa-map-marker-alt mr-2"></i>Address</label><input type="text" class="form-control" id="address" name="address" value="' . $e($address) . '" placeholder="Optional"></div>';
        $profileContent .= '<div class="form-group mb-3"><label for="newPassword"><i class="fas fa-lock mr-2"></i>New Password <span class="text-muted">(leave blank to keep current)</span></label><input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Enter new password"></div>';
        $profileContent .= '<div class="form-group mb-4"><label for="newPasswordConfirm"><i class="fas fa-lock mr-2"></i>Confirm New Password</label><input type="password" class="form-control" id="newPasswordConfirm" name="newPasswordConfirm" placeholder="Confirm new password"></div>';
        if ($createdAt instanceof \DateTimeInterface) {
            $profileContent .= '<p class="mb-3 text-muted"><i class="fas fa-calendar mr-2"></i>Member since: ' . $e($createdAt->format('F j, Y')) . '</p>';
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            $profileContent .= '<p class="mb-3"><span class="badge badge-primary">Admin</span> <a href="/dashboard" class="btn_1 btn-sm ml-2">Go to Dashboard</a></p>';
        }
        $profileContent .= '<button type="submit" class="btn_1">Save Changes</button></form>';

        $projectDir = $this->getParameter('kernel.project_dir');
        $htmlPath = $projectDir . '/public/front_template/profile.html';

        if (!is_file($htmlPath)) {
            throw $this->createNotFoundException('Profile template not found.');
        }

        $html = file_get_contents($htmlPath);
        $baseTag = '<base href="' . $request->getBasePath() . '/front_template/">';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n    " . $baseTag, $html, 1);
        $html = str_replace('<!-- AUTH_BUTTONS -->', $this->getAuthButtonsHtml(), $html);
        $html = str_replace('<!-- PROFILE_CONTENT -->', $profileContent, $html);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    #[Route('/', name: 'front_index')]
    public function index(Request $request): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $htmlPath = $projectDir . '/public/front_template/index.html';

        if (!is_file($htmlPath)) {
            throw $this->createNotFoundException('Front template not found.');
        }

        $html = file_get_contents($htmlPath);
        $baseTag = '<base href="' . $request->getBasePath() . '/front_template/">';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n    " . $baseTag, $html, 1);
        $html = str_replace('<!-- AUTH_BUTTONS -->', $this->getAuthButtonsHtml(), $html);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
