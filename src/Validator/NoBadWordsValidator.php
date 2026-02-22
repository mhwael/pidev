<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NoBadWordsValidator extends ConstraintValidator
{
    public function __construct(
        private HttpClientInterface $client
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoBadWords) {
            throw new UnexpectedTypeException($constraint, NoBadWords::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // 🚀 Call the PurgoMalum API
        try {
            $response = $this->client->request('GET', 'https://www.purgomalum.com/service/containsprofanity', [
                'query' => [
                    'text' => (string) $value
                ]
            ]);

            // The API returns "true" if it finds bad words
            if ($response->getContent() === 'true') {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        } catch (\Exception $e) {
            // If the API is down, we let it pass (Fail Open) to not break the site
        }
    }
}