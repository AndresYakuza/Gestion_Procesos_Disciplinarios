<?php

namespace App\Services;

use CodeIgniter\Email\Email;
use App\Models\FurdModel;
use App\Models\FurdSoporteModel;
use App\Models\FurdCitacionNotificacionModel;



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

        if (empty($soporte['notificado_cliente_at'])) {
            $sm = new FurdSoporteModel();
            $sm->update((int) $soporte['id'], [
                'notificado_cliente_at' => date('Y-m-d H:i:s'),
            ]);
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

    public function notifySoporteRecordatorio(array $furd, array $soporte): bool
    {
        $toCliente = $furd['correo_cliente'] ?? '';
        if ($toCliente === '') {
            return false;
        }

        // Ajusta esto a tu config real
        $emailConfig = config('Email');
        $gpdConfig   = config('Gpd');
        $correoGestion = !empty($gpdConfig->correoGestionProcesos)
            ? trim((string) $gpdConfig->correoGestionProcesos)
            : null;

        $subject = 'Recordatorio de respuesta – Proceso disciplinario ' . ($furd['consecutivo'] ?? '');
        $body    = view('emails/furd/soporte_recordatorio', [
            'furd'    => $furd,
            'soporte' => $soporte,
        ]);

        $this->email->clear(true);
        $this->email->setMailType('html');
        $this->email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
        $this->email->setTo($toCliente);
        if ($correoGestion) {
            $this->email->setCC($correoGestion);
        }
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        $this->email->setAltMessage(
            "Recordatorio de respuesta para el proceso disciplinario {$furd['consecutivo']}.\n" .
                "Trabajador: {$furd['nombre_completo']} (CC {$furd['cedula']})."
        );

        if (! $this->email->send()) {
            log_message('error', 'Error enviando recordatorio FURD {id}. Debug: {debug}', [
                'id'    => $furd['id'] ?? null,
                'debug' => $this->email->printDebugger(['headers', 'subject']),
            ]);
            return false;
        }

        (new FurdSoporteModel())->update((int) $soporte['id'], [
            'recordatorio_cliente_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function notifySoporteAutoArchivado(array $furd, array $soporte): bool
    {
        $toTrabajador = trim((string) ($furd['correo'] ?? ''));
        $toCliente    = trim((string) ($furd['correo_cliente'] ?? ''));

        // Si no hay ningún correo destino, no hacemos nada
        if ($toTrabajador === '' && $toCliente === '') {
            return false;
        }

        $emailConfig = config('Email');
        $gpdConfig   = config('Gpd');

        $correoGestion = !empty($gpdConfig->correoGestionProcesos)
            ? trim((string) $gpdConfig->correoGestionProcesos)
            : trim((string) $emailConfig->fromEmail);

        $subject = 'Archivo automático – Proceso disciplinario ' . ($furd['consecutivo'] ?? '');
        $body    = view('emails/furd/soporte_auto_archivado', [
            'furd'    => $furd,
            'soporte' => $soporte,
        ]);

        // Construimos lista de destinatarios (trabajador + cliente)
        $destinatarios = array_filter([$toTrabajador, $toCliente]);

        $this->email->clear(true);
        $this->email->setMailType('html');
        $this->email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
        $this->email->setTo($destinatarios);
        // Gestión de Procesos Disciplinarios siempre en copia
        if (!empty($correoGestion)) {
            $this->email->setCC($correoGestion);
        }
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        $this->email->setAltMessage(
            "Se ha archivado automáticamente el proceso disciplinario {$furd['consecutivo']} "
                . "por vencimiento del plazo de respuesta del cliente."
        );

        $ok = $this->email->send();

        if (!$ok) {
            log_message('error', 'Error enviando auto-archivo FURD {id}. Debug: {debug}', [
                'id'    => $furd['id'] ?? null,
                'debug' => $this->email->printDebugger(['headers', 'subject']),
            ]);
        }

        $now = date('Y-m-d H:i:s');

        // Marcar auto-archivo en soporte
        $sm = new FurdSoporteModel();
        $sm->update((int) $soporte['id'], [
            'auto_archivado_at' => $now,
        ]);

        // Actualizar estado del FURD
        $fm = new FurdModel();
        $fm->update((int) $furd['id'], [
            'estado'     => 'archivado',
            'updated_at' => $now,
        ]);

        return $ok;
    }

    public function notifyCitacionTrabajador(array $furd, array $citacion, ?array $docxMeta = null): bool
    {
        $to = trim((string)($furd['correo'] ?? ''));
        if ($to === '') {
            log_message('warning', 'Sin correo de trabajador para citación. FURD ID: {id}', [
                'id' => $furd['id'] ?? null,
            ]);
            return false;
        }

        $emailConfig = config('Email');

        $subject = sprintf(
            'Notificación de citación – Proceso %s',
            $furd['consecutivo'] ?? ''
        );

        $body = view('emails/furd/citacion_trabajador', [
            'furd'     => $furd,
            'citacion' => $citacion,
        ], ['debug' => false]);

        $this->email->clear(true);
        $this->email->setMailType('html');
        $this->email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
        $this->email->setTo($to);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        $tmpDocxPath = null;

        log_message('debug', '[CITACION] docxMeta recibido en notify: {meta}', [
            'meta' => json_encode($docxMeta),
        ]);

        if (!empty($docxMeta['docx_file_id'])) {
            try {
                $g = new \App\Libraries\GDrive();
                $binary = $g->downloadFile((string)$docxMeta['docx_file_id']);

                $tmpDir = WRITEPATH . 'tmp';
                if (!is_dir($tmpDir)) {
                    @mkdir($tmpDir, 0775, true);
                }

                $safeName = trim((string)($docxMeta['docx_name'] ?? ''));
                if ($safeName === '') {
                    $safeName = 'CITACION_' . preg_replace('/[^\w\-]+/', '_', (string)($furd['consecutivo'] ?? 'PD')) . '.docx';
                }

                $tmpDocxPath = $tmpDir . DIRECTORY_SEPARATOR . $safeName;

                if (file_put_contents($tmpDocxPath, $binary) === false) {
                    throw new \RuntimeException('No se pudo escribir el DOCX temporal en disco.');
                }

                log_message('debug', '[CITACION] DOCX temporal preparado para correo: {path}', [
                    'path' => $tmpDocxPath,
                ]);
            } catch (\Throwable $e) {
                log_message('error', '[CITACION] No se pudo preparar DOCX adjunto para correo: {msg}', [
                    'msg' => $e->getMessage(),
                ]);
                $tmpDocxPath = null;
            }
        }

        if ($tmpDocxPath && is_file($tmpDocxPath)) {
            log_message('debug', '[CITACION] Adjuntando DOCX: {path}', ['path' => $tmpDocxPath]);
            $this->email->attach($tmpDocxPath);
        } else {
            log_message('error', '[CITACION] No se adjunta DOCX porque no existe archivo temporal.');
        }

        $this->email->setAltMessage(
            "Tiene una nueva citación del proceso {$furd['consecutivo']}.\n" .
                "Fecha: " . ($citacion['fecha_evento'] ?? 'N/D') . "\n" .
                "Hora: " . ($citacion['hora'] ?? 'N/D') . "\n" .
                "Medio: " . ($citacion['medio'] ?? 'N/D') . "\n\n" .
                "Se adjunta el documento formal de citación en formato RH-FO67."
        );

        $ok = $this->email->send();

        $notif = new FurdCitacionNotificacionModel();
        $notif->insert([
            'citacion_id'   => (int)($citacion['id'] ?? 0),
            'canal'         => 'email',
            'destinatario'  => $to,
            'estado'        => $ok ? 'enviado' : 'fallido',
            'mensaje_id'    => null,
            'error'         => $ok ? null : substr((string)$this->email->printDebugger(['headers', 'subject']), 0, 500),
            'notificado_at' => date('Y-m-d H:i:s'),
        ]);

        if ($tmpDocxPath && is_file($tmpDocxPath)) {
            @unlink($tmpDocxPath);
        }

        if (!$ok) {
            log_message('error', 'Error enviando citación FURD {id}. Debug: {debug}', [
                'id'    => $furd['id'] ?? null,
                'debug' => $this->email->printDebugger(['headers', 'subject']),
            ]);
            return false;
        }

        return true;
    }
}
