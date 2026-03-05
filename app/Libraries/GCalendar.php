<?php

namespace App\Libraries;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;

class GCalendar
{
    protected Calendar $service;
    protected string $calendarId;

    public function __construct()
    {
        $jsonPath = (string) env('GDRIVE_SA_JSON', '');
        $jsonPath = $this->resolvePath(
            $jsonPath !== '' ? $jsonPath : WRITEPATH . 'keys/sa.json'
        );

        if (!is_file($jsonPath)) {
            throw new \RuntimeException("No se encontró JSON de Service Account en {$jsonPath}");
        }

        $client = new Client();
        $client->setAuthConfig($jsonPath);
        $client->setScopes([Calendar::CALENDAR]);
        $client->setAccessType('offline');

        // 👇 IMPERSONACIÓN (dominio ya tiene delegación configurada)
        $impersonated = (string) env('GCALENDAR_IMPERSONATE', '');
        if ($impersonated !== '') {
            $client->setSubject($impersonated);
        }

        $this->service    = new Calendar($client);
        $this->calendarId = (string) env('GCALENDAR_CALENDAR_ID', 'primary');
    }

    /**
     * Crea un evento con Google Meet y devuelve el enlace.
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param array $opts ['summary','description','attendees' => ['correo1','correo2',...]]
     */
public function crearEventoMeet(\DateTimeInterface $start, \DateTimeInterface $end, array $opts = []): ?string
{
    $event = new Event([
        'summary'     => $opts['summary']     ?? 'Citación a descargos',
        'description' => $opts['description'] ?? '',
        'start'       => new EventDateTime([
            'dateTime' => $start->format(DATE_RFC3339),
            'timeZone' => 'America/Bogota',
        ]),
        'end'         => new EventDateTime([
            'dateTime' => $end->format(DATE_RFC3339),
            'timeZone' => 'America/Bogota',
        ]),
    ]);

    // ✅ Volvemos a usar asistentes (ya tenemos delegación de dominio)
    $attendees = [];
    foreach (($opts['attendees'] ?? []) as $mail) {
        $mail = trim((string) $mail);
        if ($mail !== '') {
            $attendees[] = ['email' => $mail];
        }
    }
    if (!empty($attendees)) {
        $event->setAttendees($attendees);
    }

    // ✅ Pedir explícitamente un Hangouts/Meet
    $confKey = new ConferenceSolutionKey();
    $confKey->setType('hangoutsMeet'); // valor correcto para Meet

    $req = new CreateConferenceRequest();
    $req->setRequestId(uniqid('furd_', true));  // obligatorio y único
    $req->setConferenceSolutionKey($confKey);

    $confData = new ConferenceData();
    $confData->setCreateRequest($req);

    $event->setConferenceData($confData);

    // Crear evento en el calendario (IMPORTANTE: conferenceDataVersion=1)
    $created = $this->service->events->insert(
        $this->calendarId,
        $event,
        ['conferenceDataVersion' => 1]
    );

    $link = $created->getHangoutLink() ?: null;

    log_message('debug', '[GCAL] Evento creado id={id}, link={link}', [
        'id'   => $created->getId(),
        'link' => $link,
    ]);

    return $link;
}

    /**
     * Copiado de GDrive: resuelve rutas relativas tipo "writable/..."
     */
    protected function resolvePath(string $p): string
    {
        if ($p === '') return $p;

        // Absoluta (Unix) o tipo C:\ en Windows
        if ($p[0] === '/' || preg_match('~^[A-Za-z]:\\\\~', $p)) {
            return $p;
        }

        if (strpos($p, 'writable/') === 0) {
            $candidate = WRITEPATH . substr($p, strlen('writable/'));
            if (is_file($candidate)) return $candidate;
        }

        $candidates = [
            ROOTPATH . ltrim($p, '/\\'),
            WRITEPATH . ltrim($p, '/\\'),
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) return $c;
        }

        return $p;
    }
}
