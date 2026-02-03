<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserManagementController extends AbstractController
{
    #[Route('/dashboard/users', name: 'app_user_management', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findBy([], ['id' => 'ASC']);

        $userRows = '';
        foreach ($users as $user) {
            $e = fn($s) => htmlspecialchars((string) $s, \ENT_QUOTES, 'UTF-8');
            $createdAt = $user->getCreatedAt() instanceof \DateTimeInterface ? $user->getCreatedAt()->format('Y-m-d') : '-';
            $userRows .= '<tr>
                <td>' . $e($user->getId()) . '</td>
                <td>' . $e($user->getFirstName()) . '</td>
                <td>' . $e($user->getLastName()) . '</td>
                <td>' . $e($user->getEmail()) . '</td>
                <td>' . $e($user->getPhone() ?? '-') . '</td>
                <td>' . $e($user->getAddress() ?? '-') . '</td>
                <td>' . $e(implode(', ', $user->getRoles())) . '</td>
                <td>' . $e($createdAt) . '</td>
                <td class="text-nowrap">
                    <a href="/dashboard/users/' . $user->getId() . '/edit" class="btn btn-sm btn-primary me-1"><i class="feather-edit-2"></i> Edit</a>
                    <form method="post" action="/dashboard/users/' . $user->getId() . '/delete" class="d-inline" onsubmit="return confirm(\'Delete this user?\');">
                        <input type="hidden" name="_token" value="' . $this->container->get('security.csrf.token_manager')->getToken('delete' . $user->getId())->getValue() . '">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="feather-trash-2"></i> Delete</button>
                    </form>
                </td>
            </tr>';
        }
        if ($userRows === '') {
            $userRows = '<tr><td colspan="9" class="text-center text-muted">No users found.</td></tr>';
        }

        $content = '<div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title"><h5 class="m-b-10">User Management</h5></div>
                <ul class="breadcrumb ms-3 mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item">User Management</li>
                </ul>
            </div>
            <div class="page-header-right ms-auto">
                <a href="/dashboard" class="btn btn-light-brand"><i class="feather-arrow-left me-2"></i>Back to Dashboard</a>
            </div>
        </div>
        <div class="main-content">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead><tr><th>#</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Roles</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>' . $userRows . '</tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>';

        $projectDir = $this->getParameter('kernel.project_dir');
        $htmlPath = $projectDir . '/public/back_template/index.html';

        if (!is_file($htmlPath)) {
            throw $this->createNotFoundException('Back template not found.');
        }

        $html = file_get_contents($htmlPath);
        $baseTag = '<base href="' . $request->getBasePath() . '/back_template/">';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n    " . $baseTag, $html, 1);

        $html = preg_replace(
            '/<!-- \[ page-header \] start -->.*?<!-- \[ Footer \] end -->/s',
            $content,
            $html,
            1
        );

        $html = str_replace('href="index.html"', 'href="/dashboard"', $html);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    #[Route('/dashboard/users/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if ($user === null) {
            throw $this->createNotFoundException('User not found.');
        }

        $e = fn($s) => htmlspecialchars((string) $s, \ENT_QUOTES, 'UTF-8');

        if ($request->isMethod('POST')) {
            $firstName = trim((string) $request->request->get('firstName', ''));
            $lastName = trim((string) $request->request->get('lastName', ''));
            $email = trim((string) $request->request->get('email', ''));
            $phone = trim((string) $request->request->get('phone', ''));
            $address = trim((string) $request->request->get('address', ''));
            $rolesInput = $request->request->all('roles');
            $newPassword = $request->request->get('newPassword', '');

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

                $roles = in_array('ROLE_ADMIN', $rolesInput) ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
                $user->setRoles($roles);

                if ($newPassword !== '') {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                }

                $entityManager->flush();
                return $this->redirectToRoute('app_user_management');
            }
        }

        $content = $this->buildEditFormContent($user, $e);
        return $this->renderBackTemplate($request, $content, 'Edit User');
    }

    #[Route('/dashboard/users/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if ($user === null) {
            throw $this->createNotFoundException('User not found.');
        }

        $token = new CsrfToken('delete' . $id, $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_user_management');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_user_management');
    }

    private function buildEditFormContent(User $user, callable $e): string
    {
        $firstName = $e($user->getFirstName());
        $lastName = $e($user->getLastName());
        $email = $e($user->getEmail());
        $phone = $e($user->getPhone() ?? '');
        $address = $e($user->getAddress() ?? '');
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $id = $user->getId();

        return '<div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title"><h5 class="m-b-10">Edit User</h5></div>
                <ul class="breadcrumb ms-3 mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/users">User Management</a></li>
                    <li class="breadcrumb-item">Edit User</li>
                </ul>
            </div>
            <div class="page-header-right ms-auto">
                <a href="/dashboard/users" class="btn btn-light-brand"><i class="feather-arrow-left me-2"></i>Back to Users</a>
            </div>
        </div>
        <div class="main-content">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="/dashboard/users/' . $id . '/edit">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" value="' . $firstName . '" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" value="' . $lastName . '" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="' . $email . '" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="' . $phone . '" placeholder="Optional">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="' . $address . '" placeholder="Optional">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="roleAdmin" name="roles[]" value="ROLE_ADMIN" ' . ($isAdmin ? 'checked' : '') . '>
                                <label class="form-check-label" for="roleAdmin">Administrator</label>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="newPassword" class="form-label">New Password <span class="text-muted">(leave blank to keep current)</span></label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Enter new password">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="feather-save me-2"></i>Save Changes</button>
                        <a href="/dashboard/users" class="btn btn-secondary ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>';
    }

    private function renderBackTemplate(Request $request, string $content, string $pageTitle = 'Dashboard'): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $htmlPath = $projectDir . '/public/back_template/index.html';

        if (!is_file($htmlPath)) {
            throw $this->createNotFoundException('Back template not found.');
        }

        $html = file_get_contents($htmlPath);
        $baseTag = '<base href="' . $request->getBasePath() . '/back_template/">';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n    " . $baseTag, $html, 1);
        $html = preg_replace('/<!-- \[ page-header \] start -->.*?<!-- \[ Footer \] end -->/s', $content, $html, 1);
        $html = str_replace('href="index.html"', 'href="/dashboard"', $html);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
