<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoBadWords extends Constraint
{
    public string $message = 'Your comment contains inappropriate language. Please keep it clean! 🚫';
}