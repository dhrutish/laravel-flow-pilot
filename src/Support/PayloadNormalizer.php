<?php

namespace FlowPilot\LaravelFlowPilot\Support;

class PayloadNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function forStorage(array $payload): array
    {
        return $this->normalizeArray($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeArray(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            $normalized[$key] = $this->normalizeValue($key, $value);
        }

        return $normalized;
    }

    private function normalizeValue(string|int $key, mixed $value): mixed
    {
        if (is_string($key) && in_array(strtolower($key), $this->redactedKeys(), true)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            return $this->normalizeArray($value);
        }

        if (is_object($value)) {
            return ['class' => $value::class];
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    private function redactedKeys(): array
    {
        return array_map(
            fn (string $key): string => strtolower($key),
            config('flow-pilot.payloads.redact', []),
        );
    }
}
