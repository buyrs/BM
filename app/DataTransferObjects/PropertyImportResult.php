<?php

namespace App\DataTransferObjects;

class PropertyImportResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $errors = 0,
        public array $errorMessages = [],
        public bool $dryRun = false
    ) {}

    public function total(): int
    {
        return $this->created + $this->updated + $this->skipped + $this->errors;
    }

    public function successful(): int
    {
        return $this->created + $this->updated;
    }

    public function hasErrors(): bool
    {
        return $this->errors > 0 || !empty($this->errorMessages);
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'total' => $this->total(),
            'successful' => $this->successful(),
            'error_messages' => $this->errorMessages,
            'dry_run' => $this->dryRun,
        ];
    }
}