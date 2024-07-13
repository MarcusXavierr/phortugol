<?php

namespace Phortugol\Helpers;

class StringHelper
{
    public static function substring(string $source, int $start, int $end): string
    {
        $length = $end - $start;
        $result = substr($source, $start, $length);
        return $result ?: '';
    }

    /**
     * @param string[] $source
     */
    public static function arrSubstring(array $source, int $start, int $end): string
    {
        $length = $end - $start;
        $result = array_slice($source, $start, $length);
        return implode('', $result);
    }

    public static function splitString(string $source): array
    {
        $result = preg_split('//u', $source, -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($result)) {
            return (array)$result;
        }

        return [];
    }
}
