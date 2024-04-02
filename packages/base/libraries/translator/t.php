<?php

/**
 * Alias for translator::trans().
 */
function t(string $key, ?array $params = []): ?string
{
    return packages\base\translator::trans($key, $params);
}
