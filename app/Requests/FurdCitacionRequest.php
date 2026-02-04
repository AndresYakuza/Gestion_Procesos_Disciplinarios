<?php namespace App\Requests;

class FurdCitacionRequest
{
    public static function rules(): array
    {
        return [
            'consecutivo'     => 'required|is_not_unique[tbl_furd.consecutivo]',
            'fecha_evento'           => 'required|valid_date[Y-m-d]',
            'hora'            => 'required',
            'medio'           => 'required|in_list[presencial,virtual,escrito]',
            'motivo'          => 'required|min_length[3]|max_length[7000]|max_word_length[120]',

            // evidencias opcionales (si decides permitir aquí)
            'adjuntos'        => 'permit_empty',
            'motivo_recitacion' => 'required|min_length[3]|max_length[5000]|max_word_length[120]'
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
                'required'   => 'Debes indicar el hecho o motivo de la intervención.',
                'min_length' => 'Agrega un poco más de detalle al motivo.',
                'max_length' => 'El motivo es demasiado largo (máximo 7000 caracteres).',
                'max_word_length' => 'El hecho contiene una palabra demasiado larga sin espacios. Divide el texto en frases o agrega espacios.'
            ],
            'motivo_recitacion' => [
                'required'   => 'Debes indicar el motivo de la nueva citación.',
                'min_length' => 'Agrega un poco más de detalle al motivo de la nueva citación.',
                'max_length' => 'El motivo de la nueva citación es demasiado largo (máximo 5000 caracteres).',
                'max_word_length' => 'El motivo de la nueva citación contiene una palabra demasiado larga sin espacios. Divide el texto en frases o agrega espacios.'
            ]
        ];
    }
}
