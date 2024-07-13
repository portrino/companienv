<?php

namespace Companienv\DotEnvEditor\Workers\Formatters;

use Companienv\DotEnvEditor\Contracts\FormatterInterface;
use Companienv\DotEnvEditor\Exceptions\InvalidKeyException;

/**
 * The .env formatter.
 *
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class Formatter implements FormatterInterface
{
    /**
     * Formatting the key of setter to writing.
     *
     * @param string $key
     * @param bool $export optional
     *
     * @return string
     * @throws InvalidKeyException
     */
    public function formatKey(string $key, bool $export = false): string
    {
        $key = trim(str_replace(['export ', '\'', '"'], '', $key));

        if ($this->isValidKey($key) === false) {
            throw new InvalidKeyException(sprintf('There is an invalid setter key. Caught at [%s].', $key), 1720792237);
        }

        if ($export) {
            $key = 'export ' . $key;
        }

        return $key;
    }

    /**
     * Formatting the comment to writing.
     *
     * @param ?string $comment
     *
     * @return string
     */
    public function formatComment(?string $comment): string
    {
        $comment = rtrim(ltrim((string)$comment, '# '), ' ');
        $comment = preg_replace('/(\r\n|\n|\r)/', ' ', $comment);

        return ($comment !== '') ? "# {$comment}" : '';
    }

    /**
     * Build a setter from the individual components for writing.
     *
     * @param string      $key
     * @param string|null $value   optional
     * @param string|null $comment optional
     * @param bool        $export  optional
     *
     * @return string
     */
    public function formatSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false): string
    {
        $key   = $this->formatKey($key, $export);
        $value = $this->formatValue($value, $this->formatComment($comment));

        return "{$key}={$value}";
    }

    /**
     * Formatting the value of setter to writing.
     *
     * @param string|null $value   optional
     * @param string|null $comment optional
     *
     * @return string
     */
    protected function formatValue(?string $value, ?string $comment = null): string
    {
        $value       = (string)$value;
        $comment     = (string)$comment;
        $hasComment  = $comment !== '';
        $forceQuotes = $hasComment && $value === '';

        if ($forceQuotes || preg_match('/[#\s\^\$\%\&\*\?\!\(\)\{\}\[\]\"\'\\\\]|\$\{[a-zA-Z0-9_.]+\}|\\\\n/', $value) === 1) {
            $value = str_replace('\\', '\\\\', $value);
            $value = str_replace("'", "\'", $value);
            $value = "'{$value}'";
        }

        return $value . ($hasComment ? " {$comment}" : '');
    }

    /**
     * Determine if the input string is valid key.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isValidKey(string $key): bool
    {
        return preg_match('/\A[a-zA-Z0-9_.]+\z/', $key) === 1;
    }
}
