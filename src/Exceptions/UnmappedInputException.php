<?php

namespace InternetGuru\LaravelCommon\Exceptions;

use RuntimeException;

/**
 * Thrown when a string input reaches validation without resolving to a
 * sanitization pipeline.
 *
 * Sanitization is only as good as its coverage, and a silent fallback hides the
 * gaps: a field nobody declared is normalized by guesswork forever, and nobody
 * finds out. Failing on debug turns every such gap into a one-line config edit
 * at the moment it appears, while production keeps serving the request with the
 * conservative pipeline and a warning in the log.
 *
 * Developer-facing, so the message is literal English rather than a translation
 * key - it names the offending key and every way to resolve it.
 */
class UnmappedInputException extends RuntimeException
{
    public function __construct(public readonly string $key)
    {
        parent::__construct(
            "Input [$key] has no sanitization pipeline. Declare it in config/ig-common.php: "
            . "add '$key' (or a matching glob) to ig-common.sanitize.types, add one of its "
            . 'validation rules to ig-common.sanitize.types, or add it to ig-common.sanitize.except '
            . 'to leave it untouched. Set IG_SANITIZE_STRICT=false to warn instead of throwing.'
        );
    }
}
