<?php namespace App\Services;

use App\Models\FurdCitacionModel;
use App\Models\FurdDescargoModel;
use App\Models\FurdSoporteModel;
use App\Models\FurdDecisionModel;

/**
 * FurdWorkflow
 * Reglas de avance entre fases del proceso disciplinario.
 *
 * Estados esperados del FURD:
 *   - registrado  -> luego puede pasar a citación
 *   - citado      -> luego puede pasar a descargos
 *   - descargos   -> luego puede pasar a soporte
 *   - soporte     -> luego puede pasar a decisión
 *   - finalizado
 */
class FurdWorkflow
{
    protected ?string $error = null;

    public function lastError(): ?string
    {
        return $this->error;
    }

    /** Fase 2: ¿se puede iniciar CITACIÓN? */
    public function canStartCitacion(array $furd): bool
    {
        $this->error = null;

        if (!isset($furd['id'], $furd['estado'])) {
            $this->error = 'FURD inválido.';
            return false;
        }

        // Debe venir de "registrado"
        if ($furd['estado'] !== 'registro') {
            $this->error = 'El FURD no está en estado "registrado".';
            return false;
        }

        // No debe existir citación previa
        $exists = (new FurdCitacionModel())
            ->where('furd_id', (int)$furd['id'])
            ->countAllResults();

        if ($exists > 0) {
            $this->error = 'Ya existe una citación registrada para este FURD.';
            return false;
        }

        return true;
    }

    /** Fase 3: ¿se puede iniciar DESCARGOS? */
    public function canStartDescargos(array $furd): bool
    {
        $this->error = null;

        if (!isset($furd['id'], $furd['estado'])) {
            $this->error = 'FURD inválido.';
            return false;
        }

        if ($furd['estado'] !== 'citado') {
            $this->error = 'El FURD no está en estado "citado".';
            return false;
        }

        // Debe existir citación
        $hasCit = (new FurdCitacionModel())
            ->where('furd_id', (int)$furd['id'])
            ->countAllResults() > 0;

        if (!$hasCit) {
            $this->error = 'Primero registra la citación.';
            return false;
        }

        // No debe existir descargos aún
        $exists = (new FurdDescargoModel())
            ->where('furd_id', (int)$furd['id'])
            ->countAllResults();

        if ($exists > 0) {
            $this->error = 'Ya existen descargos registrados para este FURD.';
            return false;
        }

        return true;
    }

    /** Fase 4: ¿se puede iniciar SOPORTE? */
    public function canStartSoporte(array $furd): bool
    {
        $this->error = null;

        if (!isset($furd['id'], $furd['estado'])) {
            $this->error = 'FURD inválido.';
            return false;
        }

        if ($furd['estado'] !== 'descargos') {
            $this->error = 'El FURD no está en estado "descargos".';
            return false;
        }

        // Debe existir descargos
        $hasDesc = (new FurdDescargoModel())
            ->where('furd_id', (int)$furd['id'])
            ->countAllResults() > 0;

        if (!$hasDesc) {
            $this->error = 'Primero registra los descargos.';
            return false;
        }

        // No debe existir soporte aún
        $exists = (new FurdSoporteModel())
            ->where('furd_id', (int)$furd['id'])
            ->countAllResults();

        if ($exists > 0) {
            $this->error = 'Ya existe soporte registrado para este FURD.';
            return false;
        }

        return true;
    }

    /** Fase 5: ¿se puede iniciar DECISIÓN? */
    public function canStartDecision(array $furd): bool
    {
        $this->error = null;

        if (!isset($furd['id'], $furd['estado'])) {
            $this->error = 'FURD inválido.';
            return false;
        }

        if ($furd['estado'] !== 'soporte') {
            $this->error = 'El FURD no está en estado "soporte".';
            return false;
        }

        // Debe existir soporte
        $hasSup = (new FurdSoporteModel())
            ->where('furd_id', (int)$furd['id'])
            ->countAllResults() > 0;

        if (!$hasSup) {
            $this->error = 'Primero registra el soporte.';
            return false;
        }

        // No debe existir decisión aún
        $exists = (new FurdDecisionModel())
            ->where('furd_id', (int)$furd['id'])
            ->countAllResults();

        if ($exists > 0) {
            $this->error = 'Ya existe una decisión registrada para este FURD.';
            return false;
        }

        return true;
    }
}
