<?php

namespace Companienv\IO;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InputOutputInteraction implements Interaction
{
    private InputInterface $input;
    private OutputInterface $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function askConfirmation(string $question): bool
    {
        return in_array(strtolower($this->ask($question, 'y')), ['y', 'yes'], true);
    }

    public function ask(string $question, string $default = ''): string
    {
        $answer = (new QuestionHelper())->ask($this->input, $this->output, new Question($question, $default));

        if ($answer === null || ($answer === '' && $default !== '')) {
            return $this->ask($question, $default);
        }

        return $answer;
    }

    public function writeln(array|string $messageOrMessages): void
    {
        $this->output->writeln($messageOrMessages);
    }
}
