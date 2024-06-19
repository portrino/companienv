<?php

namespace Companienv\DotEnv;

class Attribute
{
    private string $name;

    /**
     * @var string[]
     */
    private array $variableNames;

    /**
     * @var string[]
     */
    private array $labels;

    /**
     * @param string $name
     * @param string[] $variableNames
     * @param string[] $labels        String key associated array of string
     */
    public function __construct(string $name, array $variableNames, array $labels)
    {
        $this->name = $name;
        $this->variableNames = $variableNames;
        $this->labels = $labels;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getVariableNames(): array
    {
        return $this->variableNames;
    }

    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }
}
