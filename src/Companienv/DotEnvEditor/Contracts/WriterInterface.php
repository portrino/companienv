<?php

namespace Companienv\DotEnvEditor\Contracts;

interface WriterInterface
{
    /**
     * Load current content into buffer.
     *
     * @param array<int, mixed[]> $content
     */
    public function setBuffer(array $content): self;

    /**
     * Return content in buffer.
     *
     * @return array<int, mixed[]>
     */
    public function getBuffer(): array;

    /**
     * Return content in buffer.
     *
     * @return string
     */
    public function getBufferAsString(): string;

    /**
     * Append empty line to buffer.
     */
    public function appendEmpty(): self;

    /**
     * Append comment line to buffer.
     *
     * @param string $comment
     */
    public function appendComment(string $comment): self;

    /**
     * Append one setter to buffer.
     *
     * @param string      $key
     * @param string|null $value
     * @param string|null $comment
     * @param bool        $export
     */
    public function appendSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false): self;

    /**
     * Update one setter in buffer.
     *
     * @param string      $key
     * @param string|null $value
     * @param string|null $comment
     * @param bool        $export
     */
    public function updateSetter(string $key, ?string $value = null, ?string $comment = null, bool $export = false): self;

    /**
     * Delete one setter in buffer.
     *
     * @param string $key
     */
    public function deleteSetter(string $key): self;

    /**
     * Save buffer to special file.
     *
     * @param string $filePath
     */
    public function saveTo(string $filePath): self;
}
