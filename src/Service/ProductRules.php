<?php

namespace App\Service;

use App\Entity\Product;

class ProductRules
{
    /**
     * Rule #1: price must be > 0
     */
    public function isValidPrice(Product $p): bool
    {
        $price = (float) ($p->getPrice() ?? 0);
        return $price > 0;
    }

    /**
     * Rule #2: stock must be >= 0
     */
    public function isValidStock(Product $p): bool
    {
        $stock = (int) ($p->getStock() ?? 0);
        return $stock >= 0;
    }

    /**
     * Global validation using both rules
     */
    public function validate(Product $p): bool
    {
        return $this->isValidPrice($p) && $this->isValidStock($p);
    }
}