<?php

namespace App\Core;

use Firebase\JWT\JWT;
use PDO;

class GoogleWalletService
{
    public static function createSaveLinkForTicket(PDO $db, int $ticketId, int $userId): string
    {
        $ticket = self::findTicket($db, $ticketId, $userId);

        if (!$ticket) {
            throw new \RuntimeException('Ticket not found.');
        }

        if ($ticket->status !== 'valid') {
            throw new \RuntimeException('Only valid tickets can be added to Google Wallet.');
        }

        if (!empty($ticket->scanned_at)) {
            throw new \RuntimeException('Used tickets cannot be added to Google Wallet.');
        }

        $config = require __DIR__ . '/../../config/config.php';
        $wallet = $config['google_wallet'] ?? [];

        if (empty($wallet['enabled'])) {
            throw new \RuntimeException('Google Wallet is not enabled yet.');
        }

        $issuerId = trim((string) ($wallet['issuer_id'] ?? ''));
        $issuerName = trim((string) ($wallet['issuer_name'] ?? 'Royal Splash'));
        $eventClassId = trim((string) ($wallet['event_class_id'] ?? ''));
        $serviceAccountPath = trim((string) ($wallet['service_account_json'] ?? ''));
        $heroImageUrl = trim((string) ($wallet['hero_image_url'] ?? ''));


        $originValue = trim((string) ($wallet['origin'] ?? ''));
    $origins = $originValue !== '' ? self::parseOrigins($originValue) : [];

        if ($issuerId === '') {
            throw new \RuntimeException('Google Wallet issuer ID is missing.');
        }

        if ($eventClassId === '') {
            throw new \RuntimeException('Google Wallet event class ID is missing.');
        }

        if ($serviceAccountPath === '' || !is_file($serviceAccountPath)) {
            throw new \RuntimeException('Google Wallet service account JSON file is missing.');
        }

        // if (empty($origins)) {
        //     throw new \RuntimeException('Google Wallet origin is missing.');
        // }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (
            empty($serviceAccount['client_email']) ||
            empty($serviceAccount['private_key'])
        ) {
            throw new \RuntimeException('Google Wallet service account JSON is invalid.');
        }

        $appUrl = rtrim($config['app_url'], '/');
        $ticketUrl = $appUrl . '/ticket?token=' . urlencode($ticket->qr_token);
$objectSuffix = 'ticket' . (int) $ticket->id . substr(sha1($ticket->qr_token), 0, 12);

$objectId = $issuerId . '.' . $objectSuffix;

        $venueName = trim($ticket->venue_name . ', ' . $ticket->city);
        $dateText = date('D, d M Y', strtotime($ticket->event_date));
        $timeText = trim((string) $ticket->start_time);

        $eventTicketObject = [
            'id' => $objectId,
            'classId' => $eventClassId,
            'state' => 'ACTIVE',

            'ticketHolderName' => $ticket->buyer_name,
            'ticketNumber' => $ticket->ticket_number,

            'ticketType' => self::localized($ticket->ticket_name),

            'reservationInfo' => [
                'confirmationCode' => $ticket->ticket_number,
            ],

            'barcode' => [
                'type' => 'QR_CODE',
                'value' => $ticketUrl,
                'alternateText' => $ticket->ticket_number,
            ],

            'textModulesData' => [
                [
                    'header' => 'Event',
                    'body' => $ticket->event_title,
                    'id' => 'event_name',
                ],
                [
                    'header' => 'Ticket Type',
                    'body' => $ticket->ticket_name,
                    'id' => 'ticket_type',
                ],
                [
                    'header' => 'Date',
                    'body' => $dateText,
                    'id' => 'event_date',
                ],
                [
                    'header' => 'Time',
                    'body' => $timeText !== '' ? $timeText : '8am till late',
                    'id' => 'event_time',
                ],
                [
                    'header' => 'Venue',
                    'body' => $venueName,
                    'id' => 'venue',
                ],
                [
                    'header' => 'Issuer',
                    'body' => $issuerName,
                    'id' => 'issuer',
                ],
            ],

            'linksModuleData' => [
                'uris' => [
                    [
                        'uri' => $ticketUrl,
                        'description' => 'Open ticket',
                        'id' => 'open_ticket',
                    ],
                ],
            ],
        ];

        if ($heroImageUrl !== '') {
            $eventTicketObject['heroImage'] = [
                'sourceUri' => [
                    'uri' => $heroImageUrl,
                ],
                'contentDescription' => self::localized('Royal Splash ticket image'),
            ];
        }

        $claims = [
            'iss' => $serviceAccount['client_email'],
            'aud' => 'google',
            'origins' => $origins,
            'typ' => 'savetowallet',
            'iat' => time(),
            'payload' => [
                'eventTicketObjects' => [
                    $eventTicketObject,
                ],
            ],
        ];

        self::saveDebugPayload($claims, $ticket->id);

        $jwt = JWT::encode(
            $claims,
            $serviceAccount['private_key'],
            'RS256'
        );

        return 'https://pay.google.com/gp/v/save/' . $jwt;
    }

    private static function findTicket(PDO $db, int $ticketId, int $userId): ?object
    {
        $stmt = $db->prepare("
            SELECT
                t.*,
                e.title AS event_title,
                e.event_date,
                e.start_time,
                e.end_time,
                e.venue_name,
                e.city
            FROM tickets t
            INNER JOIN events e ON e.id = t.event_id
            WHERE t.id = :ticket_id
            AND t.user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            ':ticket_id' => $ticketId,
            ':user_id' => $userId,
        ]);

        $ticket = $stmt->fetch();

        return $ticket ?: null;
    }

    private static function parseOrigins(string $originValue): array
    {
        $originValue = trim($originValue);

        if ($originValue === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $originValue));
        $origins = [];

        foreach ($parts as $origin) {
            if ($origin === '') {
                continue;
            }

            if (!preg_match('/^https?:\/\//i', $origin)) {
                $origin = 'https://' . $origin;
            }

            $origin = rtrim($origin, '/');

            $origins[] = $origin;
        }

        return array_values(array_unique($origins));
    }

    private static function localized(string $value): array
    {
        return [
            'defaultValue' => [
                'language' => 'en-US',
                'value' => $value,
            ],
        ];
    }

    private static function safeId(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9_]/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);
        $value = trim($value, '_');

        return $value ?: ('id_' . bin2hex(random_bytes(4)));
    }

    private static function saveDebugPayload(array $claims, int $ticketId): void
    {
        $debugDir = __DIR__ . '/../../storage/google-wallet/debug';

        if (!is_dir($debugDir)) {
            @mkdir($debugDir, 0775, true);
        }

        $file = $debugDir . '/ticket-' . $ticketId . '-' . date('Ymd-His') . '.json';

        @file_put_contents(
            $file,
            json_encode($claims, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}