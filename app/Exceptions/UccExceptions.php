<?php

namespace App\Exceptions;

class UccException extends \RuntimeException {}

class UccNotConfiguredException extends UccException {}

class UccAuthException extends UccException {}

class UccCursorExpiredException extends UccException {}

class UccRateLimitException extends UccException {}

class UccClientException extends UccException {}

class UccSyncConflictException extends UccException {}

