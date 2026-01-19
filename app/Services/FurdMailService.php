<?php

namespace App\Services;

use CodeIgniter\Email\Email;

class FurdMailService
{
    protected Email $email;

    public function __construct(?Email $email = null)
    {
        $this->email = $email ?? service('email');
    }

    /**
     * Envía al correo del cliente la decisión propuesta y la justificación
     * de la fase de soporte, para que la revise / apruebe / sugiera cambios.
     */
    public function notifySoportePropuesta(array $furd, array $soporte): bool
    {
        $to = trim((string)($furd['correo_cliente'] ?? ''));
        if ($to === '') {
            log_message('warning', 'FURD sin correo_cliente, no se envía notificación de soporte. ID: {id}', [
                'id' => $furd['id'] ?? null,
            ]);
            return false;
        }

        $emailConfig = config('Email');

        $subject = sprintf(
            'Revisión de decisión propuesta – Proceso disciplinario %s',
            $furd['consecutivo'] ?? ''
        );

        // Enlace donde el cliente podrá aprobar/rechazar/editar
        // (ajusta la ruta cuando tengas la pantalla lista)
        $urlAprobacion = site_url('soporte/revision-cliente/' . ($furd['consecutivo'] ?? ''));

        $data = [
            'furd'          => $furd,
            'soporte'       => $soporte,
            'urlAprobacion' => $urlAprobacion,
        ];

        // Render de la vista SIN comentarios de debug
        $body = view('emails/furd/soporte_propuesta', $data, ['debug' => false]);

        $this->email->clear(true);

        // Muy importante 👇
        $this->email->setMailType('html');

        $this->email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
        $this->email->setTo($to);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        // (opcional) versión de texto plano por si el cliente no soporta HTML
        $this->email->setAltMessage(
            'Se ha registrado una decisión propuesta en el proceso disciplinario '
                . ($furd['consecutivo'] ?? '')
                . "\n\nDecisión sugerida: " . ($soporte['decision_propuesta'] ?? '')
                . "\n\nJustificación:\n" . ($soporte['justificacion'] ?? '')
        );


        if (!$this->email->send()) {
            log_message('error', 'Error enviando correo de soporte FURD {id}. Debug: {debug}', [
                'id'    => $furd['id'] ?? null,
                'debug' => $this->email->printDebugger(['headers', 'subject']),
            ]);
            return false;
        }

        return true;
    }

    public function notifySoporteRespuestaCliente(array $furd, array $soporte): bool
    {
        $emailConfig = config('Email');

        // Por ahora el destinatario será el correo emisor configurado
        $to = $emailConfig->fromEmail;

        $subject = sprintf(
            'Respuesta del cliente a la decisión propuesta – %s',
            $furd['consecutivo'] ?? ''
        );

        $data = [
            'furd'    => $furd,
            'soporte' => $soporte,
        ];

        $body = view('emails/furd/soporte_respuesta_cliente', $data, ['debug' => false]);

        $this->email->clear(true);
        $this->email->setMailType('html');
        $this->email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
        $this->email->setTo($to);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        $this->email->setAltMessage(
            'El cliente respondió a la decisión propuesta del proceso '
                . ($furd['consecutivo'] ?? '')
                . "\n\nEstado: " . ($soporte['cliente_estado'] ?? 'pendiente')
                . "\nDecisión propuesta: " . ($soporte['decision_propuesta'] ?? '')
                . "\nDecisión cliente: " . ($soporte['cliente_decision'] ?? '')
                . "\nComentario: " . ($soporte['cliente_comentario'] ?? '')
        );

        if (!$this->email->send()) {
            log_message('error', 'Error enviando correo de respuesta cliente FURD {id}. Debug: {debug}', [
                'id'    => $furd['id'] ?? null,
                'debug' => $this->email->printDebugger(['headers', 'subject']),
            ]);
            return false;
        }

        return true;
    }
}
