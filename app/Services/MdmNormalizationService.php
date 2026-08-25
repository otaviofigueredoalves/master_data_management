<?php

namespace App\Services;

use App\Models\MdmEntity;

class MdmNormalizationService
{
    /**
     * Normalizes the entity payload applying domain rules and stores the
     * result in normalized_data.
     */
    public function normalize(MdmEntity $entity): MdmEntity
    {
        $source = $entity->data ?? [];

        $normalized = $this->applyRules($source);

        $entity->normalized_data = $normalized;
        $entity->save();

        return $entity->fresh();
    }

    protected function applyRules(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $normalizedKey = $this->normalizeKey((string) $key);

            if (is_array($value)) {
                $value = $this->applyRules($value);
            } elseif (is_string($value)) {
                $value = $this->normalizeValue($normalizedKey, $value);
            }

            $result[$normalizedKey] = $value;
        }

        return $result;
    }

    /**
     * snake_case keys and strip surrounding whitespace.
     */
    protected function normalizeKey(string $key): string
    {
        $key = preg_replace('/[\s\-]+/', '_', trim($key));

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
    }

    /**
     * Applies field-aware transformations (email, document numbers, names).
     */
    protected function normalizeValue(string $key, string $value): string
    {
        $value = trim($value);

        return match (true) {
            str_contains($key, 'email') => strtolower($value),
            str_contains($key, 'phone') || str_contains($key, 'cep') || $key === 'zip' => preg_replace('/[^\d+]/', '', $value) ?? $value,
            str_contains($key, 'cpf') || str_contains($key, 'cnpj') || str_contains($key, 'tax_id') => preg_replace('/\D/', '', $value) ?? $value,
            default => $value,
        };
    }
}
