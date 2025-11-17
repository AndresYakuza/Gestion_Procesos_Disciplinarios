<?php namespace App\Requests;

class FurdDescargosRequest
{
    public static function rules(): array
    {
        return [
            'consecutivo'     => 'required|is_not_unique[tbl_furd.consecutivo]',
            'fecha_evento'           => 'required|valid_date[Y-m-d]',
            'hora'   => 'required',
            'medio'  => 'required|in_list[presencial,virtual]',
            'observacion'     => 'permit_empty|min_length[3]',
            'adjuntos'        => 'permit_empty',
        ];
    }

    public static function messages(): array
    {
        return [
            'consecutivo' => [
                'required'      => 'Debes indicar el consecutivo.',
                'is_not_unique' => 'El consecutivo no existe.',
            ],
            'fecha_evento' => [
                'required'   => 'La fecha de descargos es obligatoria.',
                'valid_date' => 'La fecha de descargos no es válida.',
            ],
            'hora' => [
                'required' => 'La hora de descargos es obligatoria.',
            ],
            'medio' => [
                'required' => 'Debes indicar el medio de descargos.',
                'in_list'  => 'Medio de descargos inválido.',
            ],
        ];
    }
}
