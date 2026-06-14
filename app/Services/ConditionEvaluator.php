<?php

namespace App\Services;

class ConditionEvaluator
{
    /**
     * Evaluate a transition condition set against a map of field values.
     *
     * @param array|null $conditions  { logic: AND|OR, rules: [ {field, operator, value} ] }
     * @param array      $values      field name => value
     */
    public function passes(?array $conditions, array $values): bool
    {
        $rules = $conditions['rules'] ?? [];
        if (empty($rules)) {
            return true; // no condition => always valid
        }

        $logic = strtoupper($conditions['logic'] ?? 'AND');
        $results = array_map(fn ($rule) => $this->evaluateRule($rule, $values), $rules);

        return $logic === 'OR' ? in_array(true, $results, true) : !in_array(false, $results, true);
    }

    private function evaluateRule(array $rule, array $values): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? '==';
        $expected = $rule['value'] ?? null;
        $actual = $field !== null ? ($values[$field] ?? null) : null;

        return match ($operator) {
            '==', '=' => $this->loose($actual) == $this->loose($expected),
            '!=' => $this->loose($actual) != $this->loose($expected),
            '>' => (float) $actual > (float) $expected,
            '>=' => (float) $actual >= (float) $expected,
            '<' => (float) $actual < (float) $expected,
            '<=' => (float) $actual <= (float) $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            'empty' => $actual === null || $actual === '',
            'not_empty' => $actual !== null && $actual !== '',
            default => false,
        };
    }

    private function loose($value)
    {
        return is_string($value) ? trim($value) : $value;
    }
}
