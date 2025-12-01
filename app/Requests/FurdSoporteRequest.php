<?php namespace App\Requests;

class FurdSoporteRequest
{
    public static function rules(): array
    {
        return [
            'consecutivo'        => 'required|is_not_unique[tbl_furd.consecutivo]',
            'responsable'        => 'required|min_length[3]|max_length[150]',
            'decision_propuesta' => 'required|min_length[3]',
            'justificacion'      => 'required|min_length[10]|max_length[4000]',
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
            'responsable' => [
                'required'   => 'Debes indicar el responsable.',
                'min_length' => 'El responsable es muy corto.',
            ],
            'decision_propuesta' => [
                'required'   => 'Debes escribir la decisión propuesta.',
                'min_length' => 'La decisión propuesta es muy corta.',
            ],
            'justificacion' => [
                'required'   => 'Debes escribir la justificación de la decisión propuesta.',
                'min_length' => 'La justificación es muy corta. Por favor, detalla un poco más.',
                'max_length' => 'La justificación es demasiado larga (máximo 4000 caracteres).',
            ],
            'adjuntos' => [
                'uploaded'   => 'Ups! El o los soportes son obligatorios.',
            ],
        ];
    }
}
