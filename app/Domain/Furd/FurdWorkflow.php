<?php

namespace App\Domain\Furd;

use App\Models\FurdModel;
use App\Models\FurdCitacionModel;
use App\Models\FurdDescargoModel;
use App\Models\FurdSoporteModel;
use App\Models\FurdDecisionModel;
use DomainException;

/**
 * Reglas de negocio del flujo FURD:
 * - No se puede crear/editar una fase si la anterior está vacía.
 * - Se permite editar fases ya creadas.
 * - El estado del FURD se sincroniza con la fase más alta alcanzada.
 * - Si se elimina algo, debe eliminarse el proceso entero (este servicio asume eso).
 */
final class FurdWorkflow
{
    public function __construct(
        private FurdModel        $furd,
        private FurdCitacionModel   $citacion,
        private FurdDescargoModel   $descargos,
        private FurdSoporteModel    $soporte,
        private FurdDecisionModel   $decision,
    ) {}

    /** Verifica si existe el FURD por consecutivo; devuelve array del FURD o lanza excepción. */
    public function requireFurdByConsecutivo(string $consecutivo): array
    {
        $row = $this->furd->where('consecutivo', $consecutivo)->first();
        if (!$row) {
            throw new DomainException('No existe un FURD con el consecutivo especificado.');
        }
        return $row;
    }

    /** Retorna true si la fase está “cumplida” (tiene registro) */
    public function hasPhase(int $furdId, string $phase): bool
    {
        return match ($phase) {
            FurdPhases::REGISTRO  => (bool) $this->furd->find($furdId), // el propio registro FURD
            FurdPhases::CITACION  => $this->citacion->where('furd_id', $furdId)->countAllResults() > 0,
            FurdPhases::DESCARGOS => $this->descargos->where('furd_id', $furdId)->countAllResults() > 0,
            FurdPhases::SOPORTE   => $this->soporte->where('furd_id', $furdId)->countAllResults() > 0,
            FurdPhases::DECISION  => $this->decision->where('furd_id', $furdId)->countAllResults() > 0,
            default               => false,
        };
    }

    /* ============================================================
     *  Helpers "canStart*" usados por los controladores
     * ============================================================
     */

    /** ¿Se puede iniciar la citación para este FURD? */
    public function canStartCitacion(array $furdRow): bool
    {
        $id = (int)($furdRow['id'] ?? 0);
        if ($id <= 0) {
            return false;
        }

        // Debe existir registro
        if (!$this->hasPhase($id, FurdPhases::REGISTRO)) {
            return false;
        }

        // *** NUEVA REGLA ***
        // Permitimos más de una citación siempre que NO se haya completado
        // la fase siguiente (Descargos) ni fases posteriores.
        if ($this->hasPhase($id, FurdPhases::DESCARGOS)) {
            return false;
        }
        if ($this->hasPhase($id, FurdPhases::SOPORTE)) {
            return false;
        }
        if ($this->hasPhase($id, FurdPhases::DECISION)) {
            return false;
        }

        return true;
    }


    /** ¿Se puede iniciar el acta de descargos para este FURD? */
    public function canStartDescargos(array $furdRow): bool
    {
        $id = (int)($furdRow['id'] ?? 0);
        if ($id <= 0) return false;

        // Debe existir citación
        if (!$this->hasPhase($id, FurdPhases::CITACION)) {
            return false;
        }

        // No permitir duplicados
        if ($this->hasPhase($id, FurdPhases::DESCARGOS)) {
            return false;
        }

        // 🚫 Regla especial: si la citación fue con descargo escrito,
        // no se debe generar acta de cargos y descargos.
        if ($this->citacionEsDescargoEscrito($id)) {
            return false;
        }

        return true;
    }

    /** ¿Se puede iniciar soporte para este FURD? */
    public function canStartSoporte(array $furdRow): bool
    {
        $id = (int)($furdRow['id'] ?? 0);
        if ($id <= 0) return false;

        // Debe existir al menos citación
        if (!$this->hasPhase($id, FurdPhases::CITACION)) {
            return false;
        }

        // No permitir soporte duplicado
        if ($this->hasPhase($id, FurdPhases::SOPORTE)) {
            return false;
        }

        // ✅ Camino normal: ya hay descargos
        if ($this->hasPhase($id, FurdPhases::DESCARGOS)) {
            return true;
        }

        // ✅ Camino especial: citación con descargo escrito
        if ($this->citacionEsDescargoEscrito($id)) {
            return true;
        }

        // ❌ Ni descargos ni descargo escrito
        return false;
    }

    /** ¿Se puede iniciar decisión para este FURD? */
    public function canStartDecision(array $furdRow): bool
    {
        $id = (int)($furdRow['id'] ?? 0);
        if ($id <= 0) return false;

        if (!$this->hasPhase($id, FurdPhases::SOPORTE)) {
            return false;
        }

        if ($this->hasPhase($id, FurdPhases::DECISION)) {
            return false;
        }

        return true;
    }

    /* ============================================================
     *  Reglas generales de prerrequisito y secuencia
     * ============================================================
     */

    /** Verifica la precondición de una fase (que la anterior exista); lanza excepción si no. */
    public function assertPrerequisite(int $furdId, string $targetPhase): void
    {
        // ✅ CASO ESPECIAL: SOPORTE
        // Se permite llegar a Soporte si:
        //   - Hay Descargos (camino normal), o
        //   - No hay Descargos, pero la citación fue con descargo escrito.
        if ($targetPhase === FurdPhases::SOPORTE) {

            // Camino normal: ya existe fase Descargos
            if ($this->hasPhase($furdId, FurdPhases::DESCARGOS)) {
                return;
            }

            // Camino alterno: citación con descargo escrito
            if ($this->citacionEsDescargoEscrito($furdId)) {
                return;
            }

            throw new DomainException(
                'No puedes diligenciar Soporte sin completar Descargos o sin que la citación haya sido con descargo escrito.'
            );
        }

        // 🔁 Resto de fases siguen la lógica anterior
        $prev = FurdPhases::previousOf($targetPhase);
        if ($prev === null) return; // registro no tiene anterior

        if (!$this->hasPhase($furdId, $prev)) {
            throw new DomainException("No puedes diligenciar {$targetPhase} sin haber completado {$prev}.");
        }
    }

    /** Sincroniza el estado textual del FURD con la fase más alta alcanzada. */
    public function syncEstado(int $furdId): string
    {
        $phase = FurdPhases::REGISTRO;
        if ($this->hasPhase($furdId, FurdPhases::CITACION))  $phase = FurdPhases::CITACION;
        if ($this->hasPhase($furdId, FurdPhases::DESCARGOS)) $phase = FurdPhases::DESCARGOS;
        if ($this->hasPhase($furdId, FurdPhases::SOPORTE))   $phase = FurdPhases::SOPORTE;
        if ($this->hasPhase($furdId, FurdPhases::DECISION))  $phase = FurdPhases::DECISION;

        $this->furd->update($furdId, ['estado' => $phase]);
        return $phase;
    }

    /** Llamar después de guardar/editar una fase. */
    public function onPhaseSaved(int $furdId, string $phase): string
    {
        // (opcional) aquí podrías setear columnas *_at en tbl_furd si las tienes
        return $this->syncEstado($furdId);
    }

    /** Lanza excepción si intentan avanzar “saltando” fases. */
    public function assertSequentialAdvance(array $furdRow, string $targetPhase): void
    {
        $currentPhase = (string)($furdRow['estado'] ?? FurdPhases::REGISTRO);
        $currentIdx   = FurdPhases::indexOf($currentPhase);
        $targetIdx    = FurdPhases::indexOf($targetPhase);

        if ($targetIdx === -1) {
            throw new DomainException('Fase destino inválida.');
        }

        $furdId = isset($furdRow['id']) ? (int)$furdRow['id'] : 0;

        // ✅ CASO ESPECIAL:
        // Si estoy en CITACION y quiero ir a SOPORTE, permito el “salto”
        // SOLO si la citación fue con descargo escrito.
        if (
            $targetPhase === FurdPhases::SOPORTE &&
            $currentPhase === FurdPhases::CITACION &&
            $furdId > 0 &&
            $this->citacionEsDescargoEscrito($furdId)
        ) {
            // se permite avanzar, no se considera salto inválido
            return;
        }

        // Permitir editar (targetIdx <= currentIdx) y permitir avanzar de a una fase
        if ($targetIdx > $currentIdx + 1) {
            $next = FurdPhases::nextOf($currentPhase);
            throw new DomainException("No puedes avanzar a '{$targetPhase}' sin completar '{$next}'.");
        }
    }

    /** Retorna true si la citación del FURD se marcó como descargo escrito */
    private function citacionEsDescargoEscrito(int $furdId): bool
    {
        $row = $this->citacion
            ->where('furd_id', $furdId)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$row) {
            return false;
        }

        return isset($row['medio']) && $row['medio'] === 'escrito';
    }
}
