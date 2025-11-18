<?php namespace App\Requests;

class FurdCitacionRequest
{
    public static function rules(): array
    {
        return [
            'consecutivo'     => 'required|is_not_unique[tbl_furd.consecutivo]',
            'fecha_evento'           => 'required|valid_date[Y-m-d]',
            'hora'            => 'required',
            'medio'           => 'required|in_list[presencial,virtual]',
            'motivo'         => 'required|min_length[3]',

            // evidencias opcionales (si decides permitir aquí)
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
                'required'   => 'La fecha de citación es obligatoria.',
                'valid_date' => 'La fecha de citación no es válida.',
            ],
            'hora' => [
                'required' => 'La hora de citación es obligatoria.',
            ],
            'medio' => [
                'required' => 'Debes indicar el medio de citación.',
                'in_list'  => 'Medio de citación inválido.',
            ],
            'motivo' => [
                'required' => 'Debes indicar el hecho o motivo de la intervención.',
            ],
        ];
    }
}
