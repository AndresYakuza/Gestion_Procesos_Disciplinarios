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
        private FurdDescargoModel  $descargos,
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
            FurdPhases::REGISTRO => (bool) $this->furd->find($furdId), // el propio registro FURD
            FurdPhases::CITACION => $this->citacion->where('furd_id', $furdId)->countAllResults() > 0,
            FurdPhases::DESCARGOS => $this->descargos->where('furd_id', $furdId)->countAllResults() > 0,
            FurdPhases::SOPORTE => $this->soporte->where('furd_id', $furdId)->countAllResults() > 0,
            FurdPhases::DECISION => $this->decision->where('furd_id', $furdId)->countAllResults() > 0,
            default => false,
        };
    }

    /** Verifica la precondición de una fase (que la anterior exista); lanza excepción si no. */
    public function assertPrerequisite(int $furdId, string $targetPhase): void
    {
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
        $currentIdx = FurdPhases::indexOf((string)($furdRow['estado'] ?? FurdPhases::REGISTRO));
        $targetIdx  = FurdPhases::indexOf($targetPhase);
        if ($targetIdx === -1) {
            throw new DomainException('Fase destino inválida.');
        }

        // Permitir editar (targetIdx <= currentIdx) y permitir avanzar de a una fase.
        if ($targetIdx > $currentIdx + 1) {
            $next = FurdPhases::nextOf((string)$furdRow['estado']);
            throw new DomainException("No puedes avanzar a '{$targetPhase}' sin completar '{$next}'.");
        }
    }
}
