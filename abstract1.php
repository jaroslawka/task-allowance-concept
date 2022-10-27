<?php

interface Allowance
{
    public function getValue();

    public function setValue();
}

interface Dependencies
{
    public function setCountry(string $country);

    public function setDate(Carbon $date);

    public function setHours(int $hours);

    public function getCountry();

    public function getDate();

    public function getDays();

    public function getHours();
}

interface Calculation
{
    function calculate(Allowance $allowance, Dependencies $dependencies);
}

class GeneralRule implements Calculation
{
    protected function daysOfWeekWithoutAllowance()
    {
        return [0, 6];
    }

    protected function numberOfHoursWithoutAllowance()
    {
        return 8;
    }

    public function calculate(Allowance $allowance, Dependencies $dependencies)
    {
        if (in_array($dependencies->getDate()->dayOfWeek, $this->daysOfweekWithoutAllowance())) {
            throw new NoAllowance();

        }

        if ($dependencies->getHours() < $this->numberOfHoursWithoutAllowance()) {
            throw new NoAllowance();
        }
    }
}

class RulePL implements Calculation
{
    const FACTOR = 2;
    const DAYS = 7;

    public function calculate(Allowance $allowance, Dependencies $dependencies)
    {
        if ($dependencies->getDays() > self::DAYS) {
            $allowance->setValue($allowance->getValue() * self::FACTOR);
        }
    }
}

class RuleDE implements Calculation
{
    const FACTOR = 1.75;
    const DAYS = 7;

    public function calculate(Allowance $allowance, Dependencies $dependencies)
    {
        if ($dependencies->getDays() > self::DAYS) {
            $allowance->setValue($allowance->getValue() * self::FACTOR);
        }
    }
}

class RuleES implements Calculation
{

    protected function reduction3()
    {
        return 50; // also we can get this value from the databse
    }

    protected function reduction5()
    {
        return 25;
    }

    public function calculate(Allowance $allowance, Dependencies $dependencies)
    {
        if ($dependencies->getDays() > 3) {
            $allowance->setValue($allowance->getValue() - $this->reduction3());
        }

        if ($dependencies->getDays() > 5) {
            $allowance->setValue($allowance->getValue() - $this->reduction5());
        }
    }
}


class RepositoryDrivenRule implements Calculation
{
    protected $rulesRepository;

    public function __construct(RulesRepository $rulesRepository)
    {
        $this->rulesRepository = $rulesRepository;
    }

    public function calculate(Allowance $allowance, Dependencies $dependencies)
    {
        $country = $dependencies->getCountry();

        $rule = $this->rulesRepository->getRuleForCountry($country);

        if ($dependencies->getDays() > $rule->getDays()) {
            $allowance->setValue($allowance->getValue() * $rule->getFator());
        }
    }
}

class Calculator
{
    protected $allowance;
    protected $dependencies;
    protected $rules;

    public function __construct(Allowance $allowance, Dependencies $dependencies)
    {
        $this->allowance = $allowance;
        $this->dependencies = $dependencies;
    }

    public function addRule(Calculation $rule)
    {
        $this->rules[] = $rule;
    }

    public function getAllowance()
    {
        return $this->allowance;
    }

    public function calculate()
    {
        foreach ($this->rules AS $rule) {
            $rule->calculate($this->allowance, $this->dependencies);
        }
    }
}

