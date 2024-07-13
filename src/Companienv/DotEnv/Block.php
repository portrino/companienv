<?php

namespace Companienv\DotEnv;

class Block
{
    private string $title;
    private string $description;

    /** @var Variable[] */
    private array $variables;

    /** @var Attribute[] */
    private array $attributes;

    /**
     * @param string $title
     * @param string $description
     * @param Variable[] $variables
     * @param Attribute[] $attributes
     */
    public function __construct(string $title = '', string $description = '', array $variables = [], array $attributes = [])
    {
        $this->title = $title;
        $this->description = $description;
        $this->variables = $variables;
        $this->attributes = $attributes;
    }

    public function appendToDescription(string $string): void
    {
        $this->description .= ($this->description !== '' ? ' ' : '') . $string;
    }

    public function addVariable(Variable $variable): void
    {
        $this->variables[] = $variable;
    }

    public function addAttribute(Attribute $attribute): void
    {
        $this->attributes[] = $attribute;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Return only the variables that are in the block.
     *
     * @param Variable[] $variables
     *
     * @return Variable[]
     */
    public function getVariablesInBlock(array $variables): array
    {
        $blockVariableNames = array_map(static function (Variable $variable) {
            return $variable->getName();
        }, $this->variables);

        return array_filter($variables, static function (Variable $variable) use ($blockVariableNames) {
            return in_array($variable->getName(), $blockVariableNames, true);
        });
    }

    /**
     * @param string $name
     *
     * @return Variable|null
     */
    public function getVariable(string $name): ?Variable
    {
        foreach ($this->variables as $variable) {
            if ($variable->getName() === $name) {
                return $variable;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param Variable|null $forVariable Will return only attribute for the given variables
     *
     * @return Attribute|null
     */
    public function getAttribute(string $name, Variable $forVariable = null): ?Attribute
    {
        foreach ($this->attributes as $attribute) {
            if (
                $attribute->getName() === $name
                && (
                    $forVariable === null
                    || in_array($forVariable->getName(), $attribute->getVariableNames(), true)
                )
            ) {
                return $attribute;
            }
        }

        return null;
    }
}
