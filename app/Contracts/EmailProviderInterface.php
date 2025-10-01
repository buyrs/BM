<?php

namespace App\Contracts;

interface EmailProviderInterface
{
    /**
     * Send an email message
     *
     * @param array $emailData Email data including to, subject, body, etc.
     * @param string $messageId Unique message identifier
     * @return bool Success status
     */
    public function send(array $emailData, string $messageId): bool;

    /**
     * Validate the provider configuration
     *
     * @return bool Configuration is valid
     */
    public function validateConfiguration(): bool;

    /**
     * Get delivery status for a message (if supported by provider)
     *
     * @param string $messageId Message identifier
     * @return string Status (sent, delivered, failed, etc.)
     */
    public function getDeliveryStatus(string $messageId): string;

    /**
     * Get provider-specific configuration requirements
     *
     * @return array Required configuration keys
     */
    public function getRequiredConfig(): array;
}