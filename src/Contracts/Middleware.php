<?php

namespace Alice\Contracts;

use Alice\Context;
use Closure;

interface Middleware
{
    public function handle(Context $context, Closure $next): mixed;
}
