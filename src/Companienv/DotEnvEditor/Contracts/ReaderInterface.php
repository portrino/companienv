<?php

namespace Companienv\DotEnvEditor\Contracts;

interface ReaderInterface
{
    /**
     * Load .env file.
     *
     * @param string $filePath The path to dotenv file
     */
    public function load(string $filePath): self;

    /**
     * Get content of .env file.
     */
    public function content(): string;

    /**
     * Get information of all entries from file content.
     *
     * @param bool $withParsedData Includes the parsed data in the result
     * @return mixed[]
     */
    public function entries(bool $withParsedData = false): array;

    /**
     * Get all key information in .env file.
     *
     * @return mixed[]
     */
    public function keys(): array;
}
