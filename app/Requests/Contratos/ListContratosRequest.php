<?php
namespace App\Http\Requests\Contratos;

use CodeIgniter\HTTP\IncomingRequest;

class ListContratosRequest
{
    public int $page = 1;
    public int $perPage = 25;
    public string $sortBy = 'fecha_ingreso';
    public string $sortDir = 'desc';

    // Filtros
    public ?int $empleado_id = null;
    public ?int $proyecto_id = null;
    public ?int $activo = null;                 // 0|1
    public ?string $estado_contrato = null;     // p.ej. ACTIVO
    public ?string $contrato = null;            // exact
    public ?string $numero_documento = null;    // exact
    public ?string $nomina_like = null;         // like
    public ?string $desde_ingreso = null;       // YYYY-MM-DD
    public ?string $hasta_ingreso = null;       // YYYY-MM-DD
    public ?string $desde_retiro = null;        // YYYY-MM-DD
    public ?string $hasta_retiro = null;        // YYYY-MM-DD
    public ?string $q = null;                   // búsqueda libre

    public static function from(IncomingRequest $r): self
    {
        $self = new self();

        $self->page     = max(1, (int)($r->getGet('page') ?? 1));
        $self->perPage  = (int)($r->getGet('per_page') ?? 25);
        if ($self->perPage < 1)   $self->perPage = 25;
        if ($self->perPage > 100) $self->perPage = 100;

        $sortBy  = (string)($r->getGet('sort_by') ?? 'fecha_ingreso');
        $sortDir = strtolower((string)($r->getGet('sort_dir') ?? 'desc'));
        $self->sortBy  = in_array($sortBy, \App\Models\EmpleadoContratoModel::SORTABLE, true) ? $sortBy : 'fecha_ingreso';
        $self->sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $g = fn($k) => $r->getGet($k);

        $self->empleado_id     = ($v=$g('empleado_id'))!==null ? (int)$v : null;
        $self->proyecto_id     = ($v=$g('proyecto_id'))!==null ? (int)$v : null;
        $self->activo          = ($v=$g('activo'))!==null ? (int)($v?1:0) : null;
        $self->estado_contrato = ($v=$g('estado_contrato')) ? trim((string)$v) : null;
        $self->contrato        = ($v=$g('contrato')) ? trim((string)$v) : null;
        $self->numero_documento= ($v=$g('numero_documento')) ? trim((string)$v) : null;
        $self->nomina_like     = ($v=$g('nomina_like')) ? trim((string)$v) : null;

        $self->desde_ingreso = ($v=$g('desde_ingreso')) ? (string)$v : null;
        $self->hasta_ingreso = ($v=$g('hasta_ingreso')) ? (string)$v : null;
        $self->desde_retiro  = ($v=$g('desde_retiro'))  ? (string)$v : null;
        $self->hasta_retiro  = ($v=$g('hasta_retiro'))  ? (string)$v : null;

        $self->q = ($v=$g('q')) ? trim((string)$v) : null;

        return $self;
    }
}
