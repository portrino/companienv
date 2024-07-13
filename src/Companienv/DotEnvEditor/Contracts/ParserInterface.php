<?php

namespace Companienv\DotEnvEditor\Contracts;

interface ParserInterface
{
    /**
     * Parse dotenv file content into separate entries.
     *
     * This will produce an array of entries, each entry
     * being an informational array of starting line and raw data.
     *
     * @param string $filePath The path to dotenv file
     *
     * @return array<int, mixed[]>
     */
    public function parseFile(string $filePath): array;

    /**
     * Parses an entry data into an array of type, export allowed or not,
     * key, value, and comment information.
     *
     * @param string $data The entry data
     *
     * @return array<string, mixed>
     */
    public function parseEntry(string $data): array;
}
