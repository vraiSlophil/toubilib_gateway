<?php

declare(strict_types=1);

namespace toubilib\mailer\application\usecases;

use toubilib\mailer\application\ports\api\MessageHandlerInterface;
use toubilib\mailer\application\ports\spi\EmailSenderInterface;
use toubilib\mailer\application\ports\spi\TemplateRendererInterface;
use toubilib\mailer\infrastructure\config\MailerConfig;

final class EmailMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly EmailSenderInterface $sender,
        private readonly TemplateRendererInterface $renderer,
        private readonly MailerConfig $config
    ) {
    }

    public function handle(string $rawBody, ?array $decoded): void
    {
        if (!is_array($decoded)) {
            return;
        }

        $recipients = $this->extractRecipients($decoded);
        if ($recipients === []) {
            return;
        }

        $eventType = (string) ($decoded['event_type'] ?? $decoded['event'] ?? 'rdv.event');
        $subject = $this->formatSubject($eventType);
        $textBody = $this->formatTextBody($decoded, $eventType);
        $htmlBody = $this->formatHtmlBody($decoded, $eventType);

        $this->sender->send(
            $recipients,
            $subject,
            $textBody,
            $htmlBody,
            $this->config->fromEmail,
            $this->config->fromName
        );
    }

    /** @return array<int, array{email:string, name?:string|null}> */
    private function extractRecipients(array $payload): array
    {
        $recipients = $payload['recipients'] ?? [];
        if (!is_array($recipients)) {
            return [];
        }

        $emails = [];
        foreach ($recipients as $recipient) {
            if (!is_array($recipient)) {
                continue;
            }
            $email = $recipient['email'] ?? null;
            if (!is_string($email) || trim($email) === '') {
                continue;
            }
            $name = trim(($recipient['prenom'] ?? '') . ' ' . ($recipient['nom'] ?? ''));
            $emails[] = [
                'email' => $email,
                'name' => $name !== '' ? $name : null,
            ];
        }

        return $emails;
    }

    private function formatSubject(string $eventType): string
    {
        return match ($eventType) {
            'rdv.created' => $this->config->subjectPrefix . ' Rendez-vous créé',
            'rdv.cancelled' => $this->config->subjectPrefix . ' Rendez-vous annulé',
            default => $this->config->subjectPrefix . ' Notification Rendez-vous',
        };
    }

    private function formatTextBody(array $payload, string $eventType): string
    {
        $rdv = $payload['rdv'] ?? [];
        $profiles = $this->extractProfiles($payload);
        $debut = $this->parseDateTime($rdv['debut'] ?? null);
        $fin = $this->parseDateTime($rdv['fin'] ?? null);
        $dureeMinutes = $rdv['duree_minutes'] ?? $rdv['dureeMinutes'] ?? null;
        $motif = (string) ($rdv['motif_visite'] ?? 'N/A');

        $praticienName = trim(($profiles['praticien']['titre'] ? ($profiles['praticien']['titre'] . ' ') : '') . ($profiles['praticien']['prenom'] ?? '') . ' ' . ($profiles['praticien']['nom'] ?? ''));
        $patientName = trim(($profiles['patient']['prenom'] ?? '') . ' ' . ($profiles['patient']['nom'] ?? ''));

        $specialite = $profiles['praticien']['specialite'] ?? null;
        $specialiteLibelle = is_array($specialite) ? ($specialite['libelle'] ?? null) : null;
        $specialiteDescription = is_array($specialite) ? ($specialite['description'] ?? null) : null;

        $cabinet = $profiles['praticien']['structure'] ?? null;
        $hasCabinet = is_array($cabinet)
            && (
                (($cabinet['nom'] ?? null) !== null && (string) $cabinet['nom'] !== '')
                || (($cabinet['adresse'] ?? null) !== null && (string) $cabinet['adresse'] !== '')
                || (($cabinet['ville'] ?? null) !== null && (string) $cabinet['ville'] !== '')
                || (($cabinet['code_postal'] ?? null) !== null && (string) $cabinet['code_postal'] !== '')
                || (($cabinet['telephone'] ?? null) !== null && (string) $cabinet['telephone'] !== '')
            );

        $lines = [];
        $lines[] = 'Bonjour,';
        $lines[] = '';

        $lines[] = $eventType === 'rdv.cancelled'
            ? 'Votre rendez-vous a été annulé.'
            : 'Votre rendez-vous est confirmé.';

        $whenLineParts = [];
        $whenLineParts[] = $debut ? $debut->format('d/m/Y \\à H\\hi') : 'N/A';
        if ($fin) {
            $whenLineParts[] = '(jusqu\'à ' . $fin->format('H\\hi') . ')';
        }
        $whenLineParts[] = '— ' . ($praticienName !== '' ? $praticienName : 'N/A');
        if (is_string($specialiteLibelle) && trim($specialiteLibelle) !== '') {
            $whenLineParts[] = '(' . trim($specialiteLibelle) . ')';
        }

        $lines[] = implode(' ', $whenLineParts);
        $lines[] = '';

        $lines[] = 'Date : ' . ($debut ? $debut->format('d/m/Y') : 'N/A');
        $heure = $debut ? $debut->format('H\\hi') : 'N/A';
        if ($fin) {
            $heure .= ' — ' . $fin->format('H\\hi');
        }
        $lines[] = 'Heure : ' . $heure;
        if ($dureeMinutes !== null && $dureeMinutes !== '') {
            $lines[] = 'Durée : ' . (string) $dureeMinutes . ' min';
        }

        $lines[] = 'Motif : ' . $motif;
        if (is_string($specialiteDescription) && trim($specialiteDescription) !== '') {
            $lines[] = '  ' . trim($specialiteDescription);
        }

        $praticienLine = 'Praticien : ' . ($praticienName !== '' ? $praticienName : 'N/A');
        if (is_string($specialiteLibelle) && trim($specialiteLibelle) !== '') {
            $praticienLine .= ' — ' . trim($specialiteLibelle);
        }
        $lines[] = $praticienLine;

        if ($hasCabinet) {
            $lines[] = 'Cabinet :';
            if (($cabinet['nom'] ?? null) !== null && trim((string) $cabinet['nom']) !== '') {
                $lines[] = '  ' . trim((string) $cabinet['nom']);
            }
            $adresse = trim((string) ($cabinet['adresse'] ?? ''));
            $cp = trim((string) ($cabinet['code_postal'] ?? ''));
            $ville = trim((string) ($cabinet['ville'] ?? ''));
            if ($adresse !== '' || $cp !== '' || $ville !== '') {
                $addrLines = [];
                if ($adresse !== '') {
                    $addrLines[] = $adresse;
                }
                $cityLine = trim($cp . ' ' . $ville);
                if ($cityLine !== '') {
                    $addrLines[] = $cityLine;
                }
                foreach ($addrLines as $addrLine) {
                    $lines[] = '  ' . $addrLine;
                }
            }
            if (($cabinet['telephone'] ?? null) !== null && trim((string) $cabinet['telephone']) !== '') {
                $lines[] = '  Contact : ' . trim((string) $cabinet['telephone']);
            }
        }

        $patientLine = 'Patient : ' . ($patientName !== '' ? $patientName : 'N/A');
        $patientEmail = $profiles['patient']['email'] ?? null;
        if (is_string($patientEmail) && trim($patientEmail) !== '') {
            $patientLine .= ' — ' . trim($patientEmail);
        }
        $lines[] = $patientLine;

        $lines[] = '';
        $lines[] = 'Email automatique Toubilib. Si une information semble incorrecte, merci de contacter le cabinet.';

        return implode("\n", $lines);
    }

    private function formatHtmlBody(array $payload, string $eventType): string
    {
        $rdv = $payload['rdv'] ?? [];
        $profiles = $this->extractProfiles($payload);
        $title = match ($eventType) {
            'rdv.created' => 'Votre rendez-vous a été confirmé',
            'rdv.cancelled' => 'Votre rendez-vous a été annulé',
            default => 'Notification de rendez-vous',
        };

        $context = [
            'title' => $title,
            'event_type' => $eventType,
            'accent' => $eventType === 'rdv.cancelled' ? '#c0392b' : '#2c7be5',
            'rdv' => [
                'debut' => $rdv['debut'] ?? 'N/A',
                'fin' => $rdv['fin'] ?? 'N/A',
                'duree_minutes' => $rdv['duree_minutes'] ?? $rdv['dureeMinutes'] ?? null,
                'motif_visite' => $rdv['motif_visite'] ?? 'N/A',
            ],
            'praticien' => $profiles['praticien'],
            'patient' => $profiles['patient'],
        ];

        return $this->renderer->render('rdv_notification.html.twig', $context);
    }

    private function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{praticien: array<string, mixed>, patient: array<string, mixed>}
     */
    private function extractProfiles(array $payload): array
    {
        $empty = [
            'nom' => 'N/A',
            'prenom' => '',
            'titre' => null,
            'email' => null,
            'telephone' => null,
            'specialite' => null,
            'structure' => null,
        ];
        $profiles = [
            'praticien' => $empty,
            'patient' => $empty,
        ];

        $recipients = $payload['recipients'] ?? [];
        if (!is_array($recipients)) {
            return $profiles;
        }

        foreach ($recipients as $recipient) {
            if (!is_array($recipient) || !is_string($recipient['type'] ?? null)) {
                continue;
            }
            $type = $recipient['type'];
            if ($type !== 'praticien' && $type !== 'patient') {
                continue;
            }
            $profiles[$type] = [
                'nom' => $recipient['nom'] ?? 'N/A',
                'prenom' => $recipient['prenom'] ?? '',
                'titre' => $recipient['titre'] ?? null,
                'email' => $recipient['email'] ?? null,
                'telephone' => $recipient['telephone'] ?? null,
                'specialite' => $recipient['specialite'] ?? null,
                'structure' => $recipient['structure'] ?? null,
            ];
        }

        return $profiles;
    }
}
