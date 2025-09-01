<?php

namespace Database\Factories;

use App\Models\BailMobiliteSignature;
use App\Models\BailMobilite;
use App\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class BailMobiliteSignatureFactory extends Factory
{
    protected $model = BailMobiliteSignature::class;

    public function definition(): array
    {
        return [
            'bail_mobilite_id' => BailMobilite::factory(),
            'signature_type' => $this->faker->randomElement(['entry', 'exit']),
            'contract_template_id' => ContractTemplate::factory(),
            'tenant_signature' => $this->faker->optional()->sha256,
            'tenant_signed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'contract_pdf_path' => $this->faker->optional()->filePath(),
        ];
    }

    public function entry(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_type' => 'entry',
        ]);
    }

    public function exit(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_type' => 'exit',
        ]);
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_signature' => $this->faker->sha256,
            'tenant_signed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'contract_pdf_path' => 'contracts/' . $this->faker->uuid . '.pdf',
        ]);
    }

    public function unsigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_signature' => null,
            'tenant_signed_at' => null,
            'contract_pdf_path' => null,
        ]);
    }
}