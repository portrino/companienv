<?php

namespace Companienv;

use Companienv\DotEnv\Block;
use Companienv\DotEnv\File;
use Companienv\DotEnv\MissingVariable;
use Companienv\DotEnv\Parser;
use Companienv\DotEnvEditor\DotenvReader;
use Companienv\DotEnvEditor\DotenvWriter;
use Companienv\DotEnvEditor\Workers\Formatters\Formatter;
use Companienv\DotEnvEditor\Workers\Parsers\ParserV3;
use Companienv\IO\FileSystem\FileSystem;
use Companienv\IO\Interaction;
use Symfony\Component\Dotenv\Dotenv;

class Companion
{
    private FileSystem $fileSystem;
    private Interaction $interaction;
    private File $reference;
    private Extension $extension;
    private string $envFileName;

    public function __construct(FileSystem $fileSystem, Interaction $interaction, Extension $extension, string $envFileName = '.env', string $distFileName = '.env.dist')
    {
        $this->fileSystem = $fileSystem;
        $this->interaction = $interaction;
        $this->extension = $extension;
        $this->reference = (new Parser())->parse($fileSystem, $distFileName);
        $this->envFileName = $envFileName;
    }

    public function fillGaps(): void
    {
        $missingVariables = $this->getVariablesRequiringValues();
        if (count($missingVariables) === 0) {
            return;
        }

        $this->interaction->writeln(sprintf(
            'It looks like you are missing some configuration (%d variables). I will help you to sort this out.',
            count($missingVariables)
        ));

        if (!$this->askConfirmation('<info>Let\'s fix this? (y) </info>')) {
            $this->interaction->writeln([
                '',
                '<comment>I let you think about it then. Re-run the command to get started again.</comment>',
                '',
            ]);

            return;
        }

        foreach ($this->reference->getBlocks() as $block) {
            $this->fillBlockGaps($block, $missingVariables);
        }
    }

    /**
     * @param MissingVariable[] $missingVariables
     */
    private function fillBlockGaps(Block $block, array $missingVariables): void
    {
        $variablesInBlock = $block->getVariablesInBlock($missingVariables);
        if (count($variablesInBlock) === 0) {
            return;
        }

        if ($block->getTitle() !== '') {
            $this->interaction->writeln([
                '',
                '<info>' . $block->getTitle() . '</info>',
            ]);
        }

        if ($block->getDescription() !== '') {
            $this->interaction->writeln($block->getDescription());
        }

        $this->interaction->writeln('');

        foreach ($block->getVariables() as $variable) {
            if (isset($missingVariables[$variable->getName()])) {
                $this->writeVariable($variable->getName(), $this->extension->getVariableValue($this, $block, $variable));
            }
        }
    }

    private function writeVariable(string $name, ?string $value = null): void
    {
        if (!$this->fileSystem->exists($this->envFileName)) {
            $this->fileSystem->write($this->envFileName, '');
        }

        $variablesInFileHash = $this->getDefinedVariablesHash();

        $writer = new DotenvWriter(new Formatter());
        $reader = (new DotenvReader(new ParserV3()))->load($this->envFileName);

        foreach ($reader->entries(true) as $entry) {
            if (isset($entry['parsed_data'])) {
                switch ($entry['parsed_data']['type']) {
                    case 'comment':
                        $writer->appendComment($entry['parsed_data']['comment']);
                        break;
                    case 'empty':
                        $writer->appendEmpty();
                        break;
                    case 'setter':
                        $writer->appendSetter(
                            $entry['parsed_data']['key'],
                            $entry['parsed_data']['value'],
                            $entry['parsed_data']['comment'],
                            $entry['parsed_data']['export']
                        );
                        break;
                }
            }
        }

        if (isset($variablesInFileHash[$name])) {
            $writer->updateSetter($name, $value);
        } else {
            $writer->appendSetter($name, $value);
        }

        $this->fileSystem->write($this->envFileName, $writer->getBufferAsString());
    }

    /**
     * @return MissingVariable[]
     */
    private function getVariablesRequiringValues(): array
    {
        $variablesInFile = $this->getDefinedVariablesHash();
        $missingVariables = [];

        foreach ($this->reference->getBlocks() as $block) {
            foreach ($block->getVariables() as $variable) {
                $currentValue = $variablesInFile[$variable->getName()] ?? null;

                if ($this->extension->isVariableRequiringValue($this, $block, $variable, $currentValue) === Extension::VARIABLE_REQUIRED) {
                    $missingVariables[$variable->getName()] = new MissingVariable($variable, $currentValue);
                }
            }
        }

        return $missingVariables;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinedVariablesHash(): array
    {
        $variablesInFile = [];
        if ($this->fileSystem->exists($this->envFileName)) {
            $dotEnv = new Dotenv();
            $variablesInFile = $dotEnv->parse($this->fileSystem->getContents($this->envFileName), $this->envFileName);
        }

        return $variablesInFile;
    }

    public function askConfirmation(string $question): bool
    {
        return $this->interaction->askConfirmation($question);
    }

    public function ask(string $question, string $default = ''): string
    {
        return $this->interaction->ask($question, $default);
    }

    public function getFileSystem(): FileSystem
    {
        return $this->fileSystem;
    }
}
