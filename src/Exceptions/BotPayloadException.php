<?php

namespace InternetGuru\LaravelCommon\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown when a Livewire request carries a payload no browser could have produced.
 *
 * Scanners replay a scraped component snapshot with fuzzed values, which lands
 * arrays in scalar properties and vice versa. Left alone, the bad value travels
 * on into the method call and render passes and fails somewhere far downstream -
 * a blade view, Carbon, htmlspecialchars, array_merge - with a different message
 * every time, which is why filtering those messages never converges. Rejecting
 * the payload where it enters the component keeps that surface closed.
 *
 * The 419 status matches how Livewire itself aborts on bot type-probes in
 * HandleComponents::setComponentPropertyAwareOfTypes(), so a real client that
 * ever tripped this would get the handler's reload-the-page recovery.
 */
class BotPayloadException extends HttpException
{
    public function __construct(string $reason)
    {
        parent::__construct(419, "Malformed Livewire payload: $reason");
    }
}
