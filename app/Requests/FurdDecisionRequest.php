<?php

namespace App\Requests;

class FurdDecisionRequest
{
    public static function rules(): array
    {
        return [
            'consecutivo'   => 'required|is_not_unique[tbl_furd.consecutivo]',
            'fecha_evento'  => 'required|valid_date[Y-m-d]',
            'decision'      => 'required|min_length[3]',      // tipo: llamado, suspensión, etc.
            'decision_text' => 'permit_empty|min_length[3]|max_length[5000]|max_word_length[120]',  // detalle/fundamentación
            'adjuntos'         => 'uploaded[adjuntos]',
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
                'required'   => 'Debes indicar la fecha de la decisión.',
                'valid_date' => 'La fecha de la decisión no es válida.',
            ],
            'decision' => [
                'required'   => 'Debes elegir el tipo de decisión.',
                'min_length' => 'La decisión elegida es inválida.',
            ],
            'decision_text' => [
                'min_length' => 'Agrega un poco más de detalle a la decisión (opcional pero recomendado).',
                'max_length' => 'La fundamentación es demasiado larga (máximo 5000 caracteres).',
                'max_word_length' => 'El hecho contiene una palabra demasiado larga sin espacios. Divide el texto en frases o agrega espacios.'
            ],
            'adjuntos' => [
                'uploaded'   => 'Ups! El sorporte firmado es obligatorio, por favor adjuntar el documento.',
            ],
        ];
    }
}
