<?php

namespace App\Message;

/**
 * Sends one person's digest mail; dispatched delayed until the digest time.
 */
final class SendDigest
{
    public function __construct(public readonly int $contactId)
    {
    }
}
