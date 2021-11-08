<?php

/**
 * Alias for translator::trans()
 * 
 * @param string $key
 * @param array|null $params
 * @return string|null
 */
function t(string $key, ?array $params = []): ?string {
	return packages\base\translator::trans($key, $params);
}
