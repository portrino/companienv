<?php

namespace Companienv\IO;

interface Interaction
{
    public function askConfirmation(string $question): bool;

    public function ask(string $question, string $default = ''): string;

    /**
     * @param string|string[] $messageOrMessages
     */
    public function writeln(array|string $messageOrMessages): void;
}
