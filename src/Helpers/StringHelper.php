<?php

namespace Toyjs\Toyjs\Helpers;

class StringHelper
{
    public static function substring(string $source, int $start, int $end): string
    {
        $length = $end - $start;
        $result = substr($source, $start, $length);
        if ($result === false) {
            return "";
        } else {
            return $result;
        }
    }
}