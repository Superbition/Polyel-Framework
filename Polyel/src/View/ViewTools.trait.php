<?php

namespace Polyel\View;

trait ViewTools
{
    private function getStringsBetween($string, $startDelimiter, $endDelimiter): array
    {
        $matches = [];
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $stringStart = $stringEnd = 0;

        while (false !== ($stringStart = strpos($string, $startDelimiter, $startFrom)))
        {
            $stringStart += $startDelimiterLength;
            $stringEnd = strpos($string, $endDelimiter, $stringStart);

            if (false === $stringEnd)
            {
                break;
            }

            $matches[] = trim(substr($string, $stringStart, $stringEnd - $stringStart));
            $startFrom = $stringEnd + $endDelimiterLength;
        }

        return $matches;
    }
}