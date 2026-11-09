<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator
    ): Response {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            return $currentUser->getRole() === 'admin'
                ? $this->redirect('/admin')
                : $this->redirectToRoute('app_home');
        }

        $formData = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
        ];
        $message = '';

        if ($request->isMethod('POST')) {
            $posted = $request->request->all();
            $firstName = is_string($posted['first_name'] ?? null) ? trim($posted['first_name']) : '';
            $lastName = is_string($posted['last_name'] ?? null) ? trim($posted['last_name']) : '';
            $email = is_string($posted['email'] ?? null) ? trim($posted['email']) : '';
            $password = is_string($posted['password'] ?? null) ? $posted['password'] : '';
            $confirmPassword = is_string($posted['confirm_password'] ?? null) ? $posted['confirm_password'] : '';
            $csrfToken = is_string($posted['_csrf_token'] ?? null) ? $posted['_csrf_token'] : '';

            $formData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
            ];

            if (!$this->isCsrfTokenValid('register', $csrfToken)) {
                $message = 'Security validation failed. Please try again.';
            } elseif ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $confirmPassword === '') {
                $message = 'Please complete all required fields.';
            } elseif (strlen($firstName) > 50 || strlen($lastName) > 50) {
                $message = 'First name and last name must not exceed 50 characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Please enter a valid email address.';
            } elseif (strlen($email) > 100) {
                $message = 'Email address must not exceed 100 characters.';
            } elseif (strlen($password) < 8) {
                $message = 'Password must be at least 8 characters long.';
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $message = 'Password must contain at least one uppercase letter.';
            } elseif (!preg_match('/[a-z]/', $password)) {
                $message = 'Password must contain at least one lowercase letter.';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $message = 'Password must contain at least one number.';
            } elseif (!preg_match('/[^A-Za-z0-9\s]/', $password)) {
                $message = 'Password must contain at least one special character.';
            } elseif (preg_match('/\s/', $password)) {
                $message = 'Password must not contain spaces.';
            } elseif ($password !== $confirmPassword) {
                $message = 'Passwords do not match.';
            } elseif ($entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
                $message = 'An account with this email already exists.';
            } else {
                $user = (new User())
                    ->setFirstName($firstName)
                    ->setLastName($lastName)
                    ->setEmail($email);

                $user->setPassword($passwordHasher->hashPassword($user, $password));

                try {
                    $entityManager->persist($user);
                    $entityManager->flush();

                    // Keep the original registration behavior: log the new customer in immediately.
                    return $userAuthenticator->authenticateUser($user, $authenticator, $request);
                } catch (UniqueConstraintViolationException) {
                    $message = 'An account with this email already exists.';
                }
            }
        }

        return $this->render('auth/register.html.twig', [
            'form_data' => $formData,
            'message' => $message,
        ]);
    }
}
