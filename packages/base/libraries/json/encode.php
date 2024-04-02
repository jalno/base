<?php

namespace packages\base\json;

const PRETTY = JSON_PRETTY_PRINT;
const FORCE_OBJECT = JSON_FORCE_OBJECT;

/**
 * Returns the JSON representation of a value.
 *
 * The encoding is affected by the supplied options and additionally the encoding of float values depends on the value of serialize_precision.
 *
 * @param mixed $value   The value being encoded. Can be any type except a resource.
 *                       All string data must be UTF-8 encoded.
 * @param int   $options bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR, JSON_UNESCAPED_LINE_TERMINATORS, JSON_THROW_ON_ERROR
 * @param int   $depth   Set the maximum depth. Must be greater than zero.
 *
 * @return string returns a JSON encoded string on success
 */
function encode($value, int $options = 0, int $depth = 512): string
{
    if (0 == $options) {
        $options = JSON_UNESCAPED_UNICODE;
    }
    $json = \json_encode($value, $options, $depth);
    if (false === $json and json_last_error()) {
        throw new JsonException();
    }

    return $json;
}
