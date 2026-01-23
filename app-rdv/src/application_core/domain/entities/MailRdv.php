<?php

namespace toubilib\core\domain\entities;

use JsonSerializable;
use InvalidArgumentException;

final class MailRdv implements JsonSerializable
{

    private string $eventType;
    private Rdv $rdv;
    /** @var array<int, object|string> */
    private array $recipients;

    public function __construct(string $eventType, Rdv $rdv, array $recipients)
    {
        $this->eventType = $eventType;
        $this->rdv = $rdv;
        if (empty($recipients)) {
            throw new InvalidArgumentException('Recipients must be a non-empty array.');
        }
        foreach ($recipients as $recipient) {
            if (!$this->isValidRecipient($recipient)) {
                throw new InvalidArgumentException('One or more recipients are invalid.');
            }
        }
        $this->recipients = array_values($recipients);
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

    public function addRecipient(object|string $recipient): void
    {
        if (!$this->isValidRecipient($recipient)) {
            throw new InvalidArgumentException('Invalid recipient provided.');
        }
        $this->recipients[] = $recipient;
    }

    private function isValidRecipient(mixed $recipient): bool
    {
        $email = $this->getRecipientEmail($recipient);
        return $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'rdv' => $this->rdv,
            'recipients' => array_map([$this, 'recipientToArray'], $this->recipients),
            'recipient_emails' => $this->getRecipientEmails(),
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    private function getRecipientEmail(mixed $recipient): ?string
    {
        if (is_string($recipient)) {
            $email = trim($recipient);
            return $email !== '' ? $email : null;
        }

        if (!is_object($recipient)) {
            return null;
        }

        if (method_exists($recipient, 'getEmail')) {
            $email = $recipient->getEmail();
            if (is_string($email) && trim($email) !== '') {
                return $email;
            }
        }

        return null;
    }

    /** @return string[] */
    private function getRecipientEmails(): array
    {
        $emails = [];
        foreach ($this->recipients as $recipient) {
            $email = $this->getRecipientEmail($recipient);
            if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    private function recipientToArray(object|string $recipient): array
    {
        if (is_string($recipient)) {
            return ['type' => 'email', 'email' => $recipient];
        }

        if ($recipient instanceof Patient) {
            return [
                'type' => 'patient',
                'id' => $recipient->getId(),
                'nom' => $recipient->getNom(),
                'prenom' => $recipient->getPrenom(),
                'email' => $recipient->getEmail(),
                'telephone' => $recipient->getTelephone(),
            ];
        }

        if ($recipient instanceof Praticien) {
            $specialite = $recipient->getSpecialite();
            return [
                'type' => 'praticien',
                'id' => $recipient->getId(),
                'nom' => $recipient->getNom(),
                'prenom' => $recipient->getPrenom(),
                'titre' => $recipient->getTitre(),
                'email' => $recipient->getEmail(),
                'telephone' => $recipient->getTelephone(),
                'specialite' => [
                    'id' => $specialite->getId(),
                    'libelle' => $specialite->getLibelle(),
                    'description' => $specialite->getDescription(),
                ],
            ];
        }

        if ($recipient instanceof PraticienDetail) {
            $specialite = $recipient->getSpecialite();
            return [
                'type' => 'praticien',
                'id' => $recipient->getId(),
                'nom' => $recipient->getNom(),
                'prenom' => $recipient->getPrenom(),
                'titre' => $recipient->getTitre(),
                'email' => $recipient->getEmail(),
                'telephone' => $recipient->getTelephone(),
                'specialite' => [
                    'id' => $specialite->getId(),
                    'libelle' => $specialite->getLibelle(),
                    'description' => $specialite->getDescription(),
                ],
            ];
        }

        return ['type' => 'unknown'];
    }

}
