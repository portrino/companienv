<?php

namespace Companienv\Composer;

use Companienv\IO\Interaction;
use Composer\IO\IOInterface;

class InteractionViaComposer implements Interaction
{
    private IOInterface $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function askConfirmation(string $question): bool
    {
        if (!$this->io->isInteractive()) {
            $this->writeln('Automatically confirmed in non-interactive mode');

            return true;
        }

        return $this->io->askConfirmation($question);
    }

    public function ask(string $question, string $default = ''): string
    {
        if (!$this->io->isInteractive()) {
            $this->writeln(sprintf('Automatically returned "%s" in non-interactive mode', $default));

            return $default;
        }

        return $this->io->ask($question, $default);
    }

    public function writeln(array|string $messageOrMessages): void
    {
        $this->io->write($messageOrMessages);
    }
}
