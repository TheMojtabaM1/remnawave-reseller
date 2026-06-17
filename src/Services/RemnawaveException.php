<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Thrown when a Remnawave API call fails. Carries a Persian-friendly
 * message for the UI plus the raw status/body for logs.
 */
final class RemnawaveException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?string $rawBody = null
    ) {
        parent::__construct($message);
    }
}
