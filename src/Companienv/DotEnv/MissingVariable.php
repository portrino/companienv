<?php

namespace Companienv\DotEnv;

class MissingVariable extends Variable
{
    private ?string $currentValue;

    public function __construct(Variable $variable, string $currentValue = null)
    {
        parent::__construct($variable->getName(), $variable->getValue());

        $this->currentValue = $currentValue;
    }

    /**
     * @return string|null
     */
    public function getCurrentValue(): ?string
    {
        return $this->currentValue;
    }
}
