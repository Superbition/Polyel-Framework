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

    private function replaceTag($key, $value, &$tags, &$content)
    {
        // Check if the key we want to replace exists inside the collected tags from the content
        if(in_array($key, $tags, true))
        {
            // Automatically filter data tags for XSS prevention
            $xssEscapedData = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $content = str_replace("{{ $key }}", $xssEscapedData, $content);
        }
        else
        {
            // Else check to see if the key is matched wanting no XSS filtering
            if(in_array("!$key!", $tags, true))
            {
                // Else raw input has been requested by using {{ !data! }}
                $content = str_replace("{{ !$key! }}", $value, $content);
            }
        }
    }

    private function xssFilter($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}