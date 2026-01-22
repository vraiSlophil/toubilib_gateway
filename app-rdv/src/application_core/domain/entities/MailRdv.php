<?php

namespace toubilib\core\domain\entities;

use JsonSerializable;
use InvalidArgumentException;

final class MailRdv implements JsonSerializable
{

    private string $eventType;
    private Rdv $rdv;
    /** @var string[] */
    private array $recipients;

    public function __construct(string $eventType, Rdv $rdv, array $recipients)
    {
        $this->eventType = $eventType;
        $this->rdv = $rdv;
        $normalized = $this->normalizeRecipients($recipients);
        if ($normalized === []) {
            throw new InvalidArgumentException('Recipients must be a non-empty array of valid email addresses.');
        }
        $this->recipients = $normalized;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getRdv(): Rdv
    {
        return $this->rdv;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function addRecipient(string $recipient): void
    {
        if (!$this->isValidRecipient($recipient)) {
            throw new InvalidArgumentException('Invalid recipient provided.');
        }
        $this->recipients[] = $recipient;
    }

    private function isValidRecipient(string $recipient): bool
    {
        $value = trim($recipient);
        return $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'rdv' => $this->rdv,
            'recipients' => $this->recipients,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /** @return string[] */
    private function normalizeRecipients(array $recipients): array
    {
        $normalized = [];
        foreach ($recipients as $recipient) {
            $email = null;
            if (is_string($recipient)) {
                $email = $recipient;
            } elseif (is_object($recipient) && method_exists($recipient, 'getEmail')) {
                $email = $recipient->getEmail();
            }

            if (!is_string($email) || !$this->isValidRecipient($email)) {
                continue;
            }

            $normalized[] = $email;
        }

        return array_values(array_unique($normalized));
    }

}
