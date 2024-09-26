<?php

namespace JkOster\CronMonitor\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvalidPingStatusReceived extends Exception
{
    public static function receivedPingStatusIsInvalid(string $status): self
    {
        return new static('Invalid ping status received: ' . $status);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): Response
    {
        return response(['message' => $this->getMessage()], 400);
    }
}
