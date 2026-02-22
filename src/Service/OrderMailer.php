<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private InvoicePdfGenerator $pdfGenerator,
        private string $fromEmail
    ) {}

    public function sendOrderConfirmation(Order $order): void
    {
        $to = trim((string) $order->getCustomerEmail());
        if ($to === '') {
            return;
        }

        $from = trim((string) $this->fromEmail);
        if ($from === '' || !str_contains($from, '@')) {
            // fallback safe sender to avoid RFC error
            $from = 'no-reply@levelup.tn';
        }

        $pdf = $this->pdfGenerator->generate($order);
        $filename = 'Facture_' . ($order->getReference() ?: ('#' . $order->getId())) . '.pdf';

        $email = (new TemplatedEmail())
            ->from(new Address($from, 'LevelUp'))
            ->to(new Address($to))
            ->subject('Confirmation de commande - ' . ($order->getReference() ?: ('#' . $order->getId())))
            ->htmlTemplate('Shop/order_confirmation_email.html.twig')
            ->context(['order' => $order])
            ->attach($pdf, $filename, 'application/pdf');

        $this->mailer->send($email);
    }
}