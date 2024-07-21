<?php

namespace packages\base\Json;

/**
 * Decodes a JSON string.
 *
 * @param string $json    The json string being decoded.
 *                        This function only works with UTF-8 encoded strings.
 * @param bool   $assoc   when TRUE, returned objects will be converted into associative arrays
 * @param int    $depth   user specified recursion depth
 * @param int    $options Bitmask of JSON_BIGINT_AS_STRING, JSON_OBJECT_AS_ARRAY, JSON_THROW_ON_ERROR. The behaviour of these constants is described on the JSON constants page.
 *
 * @see http://php.net/manual/en/json.constants.php
 *
 * @return mixed the value encoded in json in appropriate PHP type. Values true, false and null are returned as TRUE, FALSE and NULL respectively.
 *
 * @throws packages\base\json\JsonException if there is error durring the process
 */
function decode(string $json, bool $assoc = true, int $depth = 512, int $options = 0)
{
    $result = json_decode($json, $assoc, $depth, $options);
    if (null === $result and json_last_error()) {
        throw new JsonException();
    }

    return $result;
}
