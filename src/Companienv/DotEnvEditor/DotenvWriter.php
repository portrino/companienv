<?php

namespace Companienv\DotEnvEditor;

use Companienv\DotEnvEditor\Contracts\FormatterInterface;
use Companienv\DotEnvEditor\Contracts\WriterInterface;
use Companienv\DotEnvEditor\Exceptions\UnableWriteToFileException;

/**
 * The DotenvWriter writer.
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvWriter implements WriterInterface
{
    /**
     * The content buffer.
     *
     * @var array<int, mixed[]>
     */
    protected array $buffer = [];

    /**
     * The instance of Formatter.
     *
     * @var FormatterInterface
     */
    protected FormatterInterface $formatter;

    /**
     * New entry template.
     *
     * @var array<string, mixed>
     */
    protected array $entryTemplate = [
        'line'    => null,
        'type'    => 'empty',
        'export'  => false,
        'key'     => '',
        'value'   => '',
        'comment' => '',
    ];

    /**
     * Create a new writer instance.
     *
     * @param FormatterInterface $formatter
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Set buffer with content.
     *
     * @param array<int, mixed[]> $content
     *
     * @return DotenvWriter
     */
    public function setBuffer(array $content = []): DotenvWriter
    {
        $this->buffer = $content;

        return $this;
    }

    /**
     * Return content in buffer.
     *
     * @return array<int, mixed[]>
     */
    public function getBuffer(): array
    {
        return $this->buffer;
    }

    /**
     * Return content in buffer as string.
     *
     * @return string
     */
    public function getBufferAsString(): string
    {
        return $this->buildTextContent();
    }

    /**
     * Append empty line to buffer.
     *
     * @return DotenvWriter
     */
    public function appendEmpty(): DotenvWriter
    {
        return $this->appendEntry([]);
    }

    /**
     * Append comment line to buffer.
     *
     * @param string $comment
     *
     * @return DotenvWriter
     */
    public function appendComment(string $comment): DotenvWriter
    {
        return $this->appendEntry([
            'type'    => 'comment',
            'comment' => $comment,
        ]);
    }

    /**
     * Append one setter to buffer.
     *
     * @param string      $key
     * @param string|null $value
     * @param string|null $comment
     * @param bool        $export
     *
     * @return DotenvWriter
     */
    public function appendSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false): DotenvWriter
    {
        return $this->appendEntry([
            'type'    => 'setter',
            'export'  => $export,
            'key'     => $key,
            'value'   => (string)$value,
            'comment' => (string)$comment,
        ]);
    }

    /**
     * Update the setter data in buffer.
     *
     * @param string      $key
     * @param string|null $value
     * @param string|null $comment
     * @param bool        $export
     *
     * @return DotenvWriter
     */
    public function updateSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false): DotenvWriter
    {
        $data = [
            'export'  => $export,
            'value'   => (string)$value,
            'comment' => (string)$comment,
        ];

        array_walk($this->buffer, static function (&$entry, $index) use ($key, $data) {
            if ($entry['type'] === 'setter' && $entry['key'] === $key) {
                $entry = array_merge($entry, $data);
            }
        });

        return $this;
    }

    /**
     * Update comment for the setter in buffer.
     *
     * @param string      $key
     * @param string|null $comment
     *
     * @return DotenvWriter
     */
    public function updateSetterComment(string $key, ?string $comment = null): DotenvWriter
    {
        $data = [
            'comment' => (string)$comment,
        ];

        array_walk($this->buffer, static function (&$entry, $index) use ($key, $data) {
            if ($entry['type'] === 'setter' && $entry['key'] === $key) {
                $entry = array_merge($entry, $data);
            }
        });

        return $this;
    }

    /**
     * Update export status for the setter in buffer.
     *
     * @param string $key
     * @param bool   $state
     *
     * @return DotenvWriter
     */
    public function updateSetterExport(string $key, bool $state): DotenvWriter
    {
        $data = [
            'export' => $state,
        ];

        array_walk($this->buffer, static function (&$entry, $index) use ($key, $data) {
            if ($entry['type'] === 'setter' && $entry['key'] === $key) {
                $entry = array_merge($entry, $data);
            }
        });

        return $this;
    }

    /**
     * Delete one setter in buffer.
     *
     * @param string $key
     *
     * @return DotenvWriter
     */
    public function deleteSetter(string $key): DotenvWriter
    {
        $this->buffer = array_values(array_filter($this->buffer, static function ($entry, $index) use ($key) {
            return $entry['type'] !== 'setter' || $entry['key'] !== $key;
        }, ARRAY_FILTER_USE_BOTH));

        return $this;
    }

    /**
     * Save buffer to special file.
     *
     * @param string $filePath
     *
     * @return DotenvWriter
     */
    public function saveTo(string $filePath): DotenvWriter
    {
        $this->ensureFileIsWritable($filePath);
        file_put_contents($filePath, $this->buildTextContent());

        return $this;
    }

    /**
     * Append new line to buffer.
     *
     * @param array<string, mixed> $data
     *
     * @return DotenvWriter
     */
    protected function appendEntry(array $data = []): DotenvWriter
    {
        $this->buffer[] = array_merge($this->entryTemplate, $data);

        return $this;
    }

    /**
     * Tests file for writability. If the file doesn't exist, check
     * the parent directory for writability so the file can be created.
     *
     * @param mixed $filePath
     *
     *
     * @throws UnableWriteToFileException
     */
    protected function ensureFileIsWritable($filePath): void
    {
        if ((is_file($filePath) && !is_writable($filePath)) || (!is_file($filePath) && !is_writable(dirname($filePath)))) {
            throw new UnableWriteToFileException(sprintf('Unable to write to the file at %s.', $filePath));
        }
    }

    /**
     * Build plain text content from buffer.
     *
     * @return string
     */
    protected function buildTextContent(): string
    {
        $data = array_map(function ($entry) {
            if ($entry['type'] === 'setter') {
                return $this->formatter->formatSetter($entry['key'], $entry['value'], $entry['comment'], $entry['export']);
            }

            if ($entry['type'] === 'comment') {
                return $this->formatter->formatComment($entry['comment']);
            }

            return '';
        }, $this->buffer);

        return implode(PHP_EOL, $data) . PHP_EOL;
    }
}
