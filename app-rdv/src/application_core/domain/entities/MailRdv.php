<?php

namespace toubilib\core\domain\entities

final class MailRdv
{
    private string $eventType;
    private Rdv $rdv;
    private array $recipients;

    public function __construct(string $eventType, Rdv $rdv, array $recipients)
    {
        $this->eventType = $eventType;
        $this->rdv = $rdv;
        if (!is_array($recipients) || empty($recipients)) {
            throw new InvalidArgumentException('Recipients must be a non-empty array of email addresses.');
        }
        if (!isValidRecipient($recipients)) {
            throw new InvalidArgumentException('One or more recipients are invalid.');
        }
        $this->recipients = $recipients;
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

    public function addRecipient(string $$recipient): void
    {
        if (!isValidRecipient($recipient)) {
            throw new InvalidArgumentException('Invalid recipient provided.');
        }
        $this->recipients[] = $recipient;
    }

    private function isValidRecipient(object $recipient): bool
    {
        return !($recipient instanceof Patient
            && filter_var($recipient->getEmail(), FILTER_VALIDATE_EMAIL) !== false
            && $recipient instanceof Patient
            && filter_var($recipient->getEmail(), FILTER_VALIDATE_EMAIL) !== false);
    }

}