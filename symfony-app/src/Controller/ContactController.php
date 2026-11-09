<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    private const SUBJECTS = [
        'product' => 'Product Inquiry',
        'preorder' => 'Pre-Order',
        'order' => 'Existing Order',
        'general' => 'General Question',
        'other' => 'Other',
    ];

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request, Connection $connection): Response
    {
        $formData = [
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
        ];
        $error = null;

        if ($request->isMethod('POST')) {
            foreach ($formData as $field => $default) {
                $value = $request->request->get($field, '');
                $formData[$field] = is_string($value) ? trim($value) : '';
            }

            $token = $request->request->get('_token', '');
            if (!is_string($token) || !$this->isCsrfTokenValid('contact_submit', $token)) {
                $error = 'Your session security token has expired. Please refresh the page and try again.';
            } elseif ($formData['name'] === '') {
                $error = 'Please enter your name.';
            } elseif (mb_strlen($formData['name']) > 100) {
                $error = 'Name is too long.';
            } elseif ($formData['email'] === '' || mb_strlen($formData['email']) > 100 || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (!array_key_exists($formData['subject'], self::SUBJECTS)) {
                $error = 'Please select a valid subject.';
            } elseif ($formData['message'] === '') {
                $error = 'Please enter your message.';
            } elseif (mb_strlen($formData['message']) > 2000) {
                $error = 'Message is too long. Please keep it within 2,000 characters.';
            }

            if ($error === null) {
                try {
                    $connection->insert('contact_messages', [
                        'name' => $formData['name'],
                        'email' => $formData['email'],
                        'subject' => self::SUBJECTS[$formData['subject']],
                        'message' => $formData['message'],
                    ]);

                    $this->addFlash(
                        'success',
                        'Your message has been sent successfully. We will get back to you as soon as possible.'
                    );

                    return $this->redirectToRoute('app_contact');
                } catch (\Throwable $exception) {
                    // Keep database details out of the customer-facing response.
                    $error = 'Unable to send your message right now. Please try again.';
                }
            }
        }

        return $this->render('contact/index.html.twig', [
            'formData' => $formData,
            'error' => $error,
            'subjectOptions' => self::SUBJECTS,
        ]);
    }
}
