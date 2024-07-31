<?php

/**
 * Alias for t().
 */
function t(string $key, ?array $params = []): ?string
{
    return __($key, $params);
}
