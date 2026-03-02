<?php

namespace App\Service;

use App\Entity\Guide;
use InvalidArgumentException;

class GuideManager
{
    public function validate(Guide $guide): bool
    {
        if (empty($guide->getTitle())) {
            throw new InvalidArgumentException('The guide title is mandatory');
        }
        if (strlen($guide->getDescription()) < 10) {
            throw new InvalidArgumentException('The description must be at least 10 characters long');
        }
        return true;
    }
}