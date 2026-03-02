<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Service\OrderRules;
use PHPUnit\Framework\TestCase;

class OrderRulesTest extends TestCase
{
    public function testValidEmailAndPhonePass(): void
    {
        $order = new Order();
        $order->setCustomerEmail('test@example.com');
        $order->setCustomerPhone('22123456');

        $rules = new OrderRules();

        $this->assertTrue($rules->isValidEmail($order));
        $this->assertTrue($rules->isValidPhone($order));
        $this->assertTrue($rules->validate($order));
    }

    public function testInvalidEmailFails(): void
    {
        $order = new Order();
        $order->setCustomerEmail('not-an-email');
        $order->setCustomerPhone('22123456');

        $rules = new OrderRules();

        $this->assertFalse($rules->isValidEmail($order));
        $this->assertFalse($rules->validate($order));
    }

    public function testInvalidPhoneFails(): void
    {
        $order = new Order();
        $order->setCustomerEmail('test@example.com');
        $order->setCustomerPhone('22-123'); // not digits + too short

        $rules = new OrderRules();

        $this->assertFalse($rules->isValidPhone($order));
        $this->assertFalse($rules->validate($order));
    }
}