<?php

namespace Alice\Contracts;

use Alice\Context;
use Closure;

/**
 * Контракт для middleware — обработчиков цепочки.
 */
interface Middleware
{
    /**
     * Обрабатывает входящий контекст и передаёт выполнение дальше по цепочке.
     *
     * @param Context $context
     * @param Closure $next
     */
    public function handle(Context $context, Closure $next);
}
