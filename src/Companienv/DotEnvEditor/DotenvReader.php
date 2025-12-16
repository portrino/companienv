<?php

namespace Companienv\DotEnvEditor;

use Companienv\DotEnvEditor\Contracts\ParserInterface;
use Companienv\DotEnvEditor\Contracts\ReaderInterface;
use Companienv\DotEnvEditor\Exceptions\UnableReadFileException;

/**
 * The DotenvReader class.
 *
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvReader implements ReaderInterface
{
    protected string $filePath;

    protected ParserInterface $parser;

    /**
     * Create a new reader instance.
     *
     * @param ParserInterface $parser
     */
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Load file.
     *
     * @param string $filePath The path to dotenv file
     *
     * @return DotenvReader
     */
    public function load(string $filePath): DotenvReader
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * Get content of file.
     *
     * @return string
     * @throws UnableReadFileException
     */
    public function content(): string
    {
        $this->ensureFileIsReadable();
        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new UnableReadFileException(sprintf('Unable to read the file at %s.', $this->filePath), 1720791765);
        }

        return $content;
    }

    /**
     * Get information of all entries from file content.
     *
     * @param bool $withParsedData Includes the parsed data in the result
     *
     * @return mixed[]
     */
    public function entries(bool $withParsedData = false): array
    {
        $entries = $this->getEntriesFromFile();

        if ($withParsedData === false) {
            return $entries;
        }

        return array_map(function (array $info) {
            $info['parsed_data'] = $this->parser->parseEntry($info['raw_data']);

            return $info;
        }, $entries);
    }

    /**
     * Get information of all keys from file content.
     *
     * @return mixed[]
     */
    public function keys(): array
    {
        $entries = $this->getEntriesFromFile();

        return array_reduce($entries, function (array $carry, array $entry) {
            $data = $this->parser->parseEntry($entry['raw_data']);

            if ($data['type'] === 'setter') {
                $carry[$data['key']] = [
                    'line'    => $entry['line'],
                    'export'  => $data['export'],
                    'value'   => $data['value'],
                    'comment' => $data['comment'],
                ];
            }

            return $carry;
        }, []);
    }

    /**
     * Read content into an array of lines with auto-detected line endings.
     *
     * @return mixed[]
     */
    protected function getEntriesFromFile(): array
    {
        $this->ensureFileIsReadable();

        return $this->parser->parseFile($this->filePath);
    }

    /**
     * Ensures the given file is readable.
     *
     *
     * @throws UnableReadFileException
     */
    protected function ensureFileIsReadable(): void
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new UnableReadFileException(sprintf('Unable to read the file at %s.', $this->filePath), 1720791765);
        }
    }
}
