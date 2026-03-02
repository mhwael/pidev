<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Service\ProductRules;
use PHPUnit\Framework\TestCase;

class ProductRulesTest extends TestCase
{
    public function testValidProductPassesRules(): void
    {
        $p = new Product();
        $p->setPrice('10.50');
        $p->setStock(5);

        $rules = new ProductRules();

        $this->assertTrue($rules->isValidPrice($p));
        $this->assertTrue($rules->isValidStock($p));
        $this->assertTrue($rules->validate($p));
    }

    public function testInvalidPriceFails(): void
    {
        $p = new Product();
        $p->setPrice('0');
        $p->setStock(5);

        $rules = new ProductRules();

        $this->assertFalse($rules->isValidPrice($p));
        $this->assertFalse($rules->validate($p));
    }

    public function testNegativeStockFails(): void
    {
        $p = new Product();
        $p->setPrice('10');
        $p->setStock(-1);

        $rules = new ProductRules();

        $this->assertFalse($rules->isValidStock($p));
        $this->assertFalse($rules->validate($p));
    }
}