<?php

namespace Companienv\DotEnv;

use Companienv\IO\FileSystem\FileSystem;
use Symfony\Component\Dotenv\Dotenv;

class Parser
{
    public function parse(FileSystem $fileSystem, string $path): File
    {
        $blocks = [];

        /** @var Block|null $block */
        $block = null;
        foreach (explode("\n", $fileSystem->getContents($path)) as $number => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#')) {
                // We see a title
                if (substr($line, 0, 2) === '##') {
                    $block = new Block(trim($line, '# '));
                    $blocks[] = $block;
                } elseif (substr($line, 0, 2) === '#~') {
                    // Ignore this comment.
                } elseif ($block !== null) {
                    if (substr($line, 0, 2) === '#+') {
                        $block->addAttribute($this->parseAttribute(substr($line, 2)));
                    } elseif (substr($line, 1, 1) === ' ') {
                        $block->appendToDescription(trim($line, '# '));
                    }
                }
            } elseif (false !== ($firstEquals = strpos($line, '='))) {
                if ($block === null) {
                    $blocks[] = $block = new Block();
                }

                $parsedLine = (new Dotenv())->parse($line);
                $name = (string)key($parsedLine);
                $value = (string)current($parsedLine);

                $block->addVariable(new Variable($name, $value));
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'The line %d of the file %s is invalid: %s',
                    $number,
                    $path,
                    $line
                ));
            }
        }

        return new File('', $blocks);
    }

    private function parseAttribute(string $string): Attribute
    {
        $variableNameRegex = '[A-Z0-9_]+';
        $valueRegex = '[^\) ]+';

        /**
         * @var array{0: non-empty-string, 1: non-empty-string, 2: string, 3?: non-empty-string, 4?: non-empty-string, 5?: non-empty-string, 6?: non-empty-string } $matches
         *
         * @example [0 => 'attributeName(ENV_VAR_FOO):(ENV_VAR_BAR=value)', 1 => 'attributeName', 2 => 'ENV_VAR_FOO', 3 => 'ENV_VAR_FOO', 4 => ':(ENV_VAR_BAR=value)', 5 => 'ENV_VAR_BAR=value', 6 => 'ENV_VAR_BAR=value']
         * @example [0 => 'attributeName(ENV_VAR_FOO)', 1 => 'attributeName', 2 => 'ENV_VAR_FOO', 3 => 'ENV_VAR_FOO']
         * @example [0 => 'attributeName(ENV_VAR_FOO ENV_VAR_BAR)', 1 => 'attributeName', 2 => 'ENV_VAR_FOO ENV_VAR_BAR', 3 => 'ENV_VAR_FOO']
         * @example [0 => 'attributeName()', 1 => 'attributeName', 2 => '']
         */
        $matches = [];
        if (preg_match('/^([a-z0-9-]+)\(((' . $variableNameRegex . ' ?)*)\)(:\(((' . $variableNameRegex . '=' . $valueRegex . ' ?)*)\))?$/', $string, $matches) === false) {
            throw new \RuntimeException(sprintf(
                'Unable to parse the given attribute: %s',
                $string
            ));
        }
        if (!isset($matches[1], $matches[2])) {
            throw new \RuntimeException(sprintf(
                'The given attribute is missing required parts: %s',
                $string
            ));
        }

        return new Attribute($matches[1], explode(' ', $matches[2]), isset($matches[6]) ? $this->dotEnvMappingToKeyBasedMapping($matches[6]) : []);
    }

    /**
     * @return array<string, mixed>
     */
    private function dotEnvMappingToKeyBasedMapping(string $dotEnvMapping): array
    {
        $mapping = [];
        $envMappings = explode(' ', $dotEnvMapping);

        foreach ($envMappings as $envMapping) {
            if (!str_contains($envMapping, '=')) {
                throw new \RuntimeException(sprintf(
                    'Could not parse attribute mapping "%s"',
                    $dotEnvMapping
                ));
            }

            [$key, $value] = explode('=', $envMapping);
            $mapping[$key] = $value;
        }

        return $mapping;
    }
}
