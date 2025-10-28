<?php
namespace App\Requests\Furd;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Validation\Validation;

class StoreFurdRequest
{
    public static function rules(): array
    {
        return [
            'colaborador_id' => 'required|is_natural_no_zero',
            'fecha_evento'   => 'required|valid_date[Y-m-d]',
            'turno'          => 'permit_empty|max_length[50]',
            'hora_evento'    => 'permit_empty|valid_date[H:i:s]',
            'supervisor_id'  => 'permit_empty|is_natural_no_zero',
            'hecho'          => 'required',
            'estado'         => 'required|in_list[registrado,citacion_generada,acta_generada,decision_emitida]',
        ];
    }

    public static function validated(IncomingRequest $req, Validation $v): array
    {
        $data = $req->getJSON(true) ?: $req->getRawInput();
        if (!$v->setRules(self::rules())->run($data)) {
            throw new \CodeIgniter\Validation\Exceptions\ValidationException(
                implode(' | ', array_map('strval', $v->getErrors()))
            );
        }
        return $data;
    }
}
