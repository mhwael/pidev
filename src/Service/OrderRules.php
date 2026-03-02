<?php

namespace App\Service;

use App\Entity\Order;

class OrderRules
{
    /**
     * Rule #1: email must be valid
     */
    public function isValidEmail(Order $order): bool
    {
        $email = trim((string) $order->getCustomerEmail());
        if ($email === '') return false;

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Rule #2: phone must be digits only and at least 8 digits
     */
    public function isValidPhone(Order $order): bool
    {
        $phone = trim((string) $order->getCustomerPhone());
        if ($phone === '') return false;

        // digits only
        if (!preg_match('/^\d+$/', $phone)) {
            return false;
        }

        // Tunisia-like: min 8 digits
        return strlen($phone) >= 8;
    }

    /**
     * Global validation
     */
    public function validate(Order $order): bool
    {
        return $this->isValidEmail($order) && $this->isValidPhone($order);
    }
}