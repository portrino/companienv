<?php

namespace Companienv\DotEnvEditor\Workers\Parsers;

use Companienv\DotEnvEditor\Exceptions\InvalidValueException;

/**
 * The Parser abstract.
 *
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
abstract class Parser
{
    /**
     * Parse dotenv file content into separate entries.
     *
     * This will produce an array of entries, each entry
     * being an informational array of starting line and raw data.
     *
     * @param string $filePath The path to dotenv file
     *
     * @return array<int, array{line: int, raw_data: string}>
     */
    public function parseFile(string $filePath): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES); // The older method
        // $lines = preg_split("/(\r\n|\n|\r)/", rtrim(@file_get_contents($filePath))); // The newer method

        $output          = [];
        $multiline       = false;
        $multilineBuffer = [];
        $lineNumber      = 0;

        if ($lines === false) {
            return $output;
        }
        foreach ($lines as $index => $line) {
            [$multiline, $line, $multilineBuffer] = $this->multilineProcess($multiline, $line, $multilineBuffer);

            if ($multiline === false) {
                $output[] = [
                    'line'     => ++$lineNumber,
                    'raw_data' => $line,
                ];

                $lineNumber = ++$index;
            }
        }

        return $output;
    }

    /**
     * Parses an entry data into an array of type, export allowed or not,
     * key, value, and comment information.
     *
     * @param string $data The entry data
     *
     * @return array{type: string, export: bool, key: string, value: string, comment: string}
     */
    public function parseEntry(string $data): array
    {
        $output = [
            'type'    => 'unknown',
            'export'  => false,
            'key'     => '',
            'value'   => '',
            'comment' => '',
        ];

        if ($this->isEmpty($data)) {
            $output['type'] = 'empty';

            return $output;
        }

        if ($this->isComment($data)) {
            $output['type']    = 'comment';
            $output['comment'] = $this->normaliseComment($data);

            return $output;
        }

        if ($this->looksLikeSetter($data)) {
            return $this->parseSetter($data);
        }

        return $output;
    }

    /**
     * Used to make all multiline variable process.
     *
     * @param bool     $multiline
     * @param string   $line
     * @param string[] $buffer
     *
     * @return array{bool,string,string[]}
     */
    protected function multilineProcess(bool $multiline, string $line, array $buffer): array
    {
        // check if $line can be multiline variable
        if ($started = $this->looksLikeMultilineStart($line)) {
            $multiline = true;
        }

        if ($multiline) {
            $buffer[] = $line;

            if ($this->looksLikeMultilineStop($line, $started)) {
                $multiline = false;
                $line      = implode(PHP_EOL, $buffer);
                $buffer    = [];
            }
        }

        return [$multiline, $line, $buffer];
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeMultilineStart(string $line): bool
    {
        if (str_contains($line, '="') === false) {
            return false;
        }

        return $this->looksLikeMultilineStop($line, true) === false;
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     * @param bool   $started
     *
     * @return bool
     */
    protected function looksLikeMultilineStop(string $line, bool $started): bool
    {
        if ($line === '"') {
            return true;
        }

        $seen = $started ? 0 : 1;

        foreach ($this->getCharPairs(str_replace('\\\\', '', $line)) as $pair) {
            if ($pair[0] !== '\\' && $pair[1] === '"') {
                ++$seen;
            }
        }

        return $seen > 1;
    }

    /**
     * Get all pairs of adjacent characters within the line.
     *
     * @param string $line
     *
     * @return mixed[]
     */
    protected function getCharPairs(string $line): array
    {
        $chars = str_split($line);

        return array_map(null, $chars, array_slice($chars, 1));
    }

    /**
     * Parses a setter into an array of type, export allowed or not,
     * key, value, and comment information.
     *
     * @param string $setter
     *
     * @return array{type: string, export: bool, key: string, value: string, comment: string}
     */
    protected function parseSetter(string $setter): array
    {
        [$key, $data] = array_map('trim', explode('=', $setter, 2));

        $output = [
            'type'    => 'setter',
            'export'  => $this->isExportKey($key),
            'key'     => $this->normaliseKey($key),
            'value'   => '',
            'comment' => '',
        ];

        [$output['value'], $output['comment']] = $this->parseSetterData($data);

        return $output;
    }

    /**
     * Normalising the key of setter to output.
     *
     * @param string $key
     *
     * @return string
     */
    protected function normaliseKey(string $key): string
    {
        return trim(str_replace(['export ', '\'', '"'], '', $key));
    }

    /**
     * Normalising the comment to output.
     *
     * @param string $comment
     *
     * @return string
     */
    protected function normaliseComment(string $comment): string
    {
        return rtrim(ltrim($comment, '# '), ' ');
    }

    /**
     * Determine if the entry in the file is empty line.
     *
     * @param string $data
     *
     * @return bool
     */
    protected function isEmpty(string $data): bool
    {
        return trim($data) === '';
    }

    /**
     * Determine if the entry in the file is a comment line, e.g. begins with a #.
     *
     * @param string $data
     *
     * @return bool
     */
    protected function isComment(string $data): bool
    {
        $data = ltrim($data);

        return isset($data[0]) && $data[0] === '#';
    }

    /**
     * Determine if the given entry looks like it's setting a key.
     *
     * @param string $data
     *
     * @return bool
     */
    protected function looksLikeSetter(string $data): bool
    {
        return str_contains($data, '=') && str_starts_with($data, '=') === false;
    }

    /**
     * Determine if the given key begins with 'export '.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isExportKey(string $key): bool
    {
        $pattern = '/^export\h.+$/';

        return (bool)preg_match($pattern, trim($key));
    }

    /**
     * Generate a friendly error message.
     *
     * @param string $cause
     * @param string $subject
     *
     * @return string
     */
    protected function getErrorMessage(string $cause, string $subject): string
    {
        return sprintf(
            'Failed to parse dotenv setter value due to %s. Failed at [%s].',
            $cause,
            strtok($subject, "\n")
        );
    }

    /**
     * Parse setter data into array of value, comment information.
     *
     * @param string|null $data
     *
     * @return mixed[]
     *
     * @throws InvalidValueException
     */
    abstract protected function parseSetterData(?string $data = null): array;
}
