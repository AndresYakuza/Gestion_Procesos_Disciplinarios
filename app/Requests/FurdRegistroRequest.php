<?php namespace App\Requests;

class FurdRegistroRequest
{
    public static function rules(): array
    {
        return [
            'cedula'          => 'required|min_length[5]|max_length[30]',
            'fecha_evento'    => 'required|valid_date[Y-m-d]',
            'hora'            => 'required',
            'superior'        => 'required|max_length[150]',
            'hecho'           => 'required|min_length[5]',

            'correo'          => 'permit_empty|valid_email|max_length[150]',
            'empresa_usuaria' => 'required|max_length[180]',
            'nombre_completo' => 'required|max_length[180]',
            'expedida_en'     => 'required|max_length[120]',

            'faltas'          => 'required',
            'faltas.*'        => 'required|integer',

            // 👉 Los adjuntos siguen siendo opcionales
            'evidencias'      => 'permit_empty',
        ];
    }

    public static function messages(): array
    {
        return [
            'cedula' => [
                'required'   => 'La cédula es obligatoria.',
                'min_length' => 'La cédula es muy corta.',
                'max_length' => 'La cédula es muy larga.',
            ],
            'fecha_evento' => [
                'required'   => 'La fecha del evento es obligatoria.',
                'valid_date' => 'La fecha del evento no es válida.',
            ],
            'hora' => [
                'required' => 'La hora del evento es obligatoria.',
            ],
            'superior' => [
                'required'   => 'El nombre del superior es obligatorio.',
                'max_length' => 'El nombre del superior es muy largo.',
            ],
            'hecho' => [
                'required'   => 'Describe el hecho o motivo.',
                'min_length' => 'Agrega más detalle al hecho.',
            ],
            'correo' => [
                'valid_email' => 'El correo no es válido.',
            ],
            'empresa_usuaria' => [
                'required'   => 'La empresa usuaria es obligatoria.',
                'max_length' => 'La empresa usuaria es muy larga.',
            ],
            'nombre_completo' => [
                'required'   => 'El nombre del trabajador es obligatorio.',
                'max_length' => 'El nombre del trabajador es muy largo.',
            ],
            'expedida_en' => [
                'required'   => 'La ciudad de expedición es obligatoria.',
                'max_length' => 'La ciudad de expedición es muy larga.',
            ],
            'faltas' => [
                'required' => 'Debes seleccionar al menos una falta.',
            ],
            'faltas.*' => [
                'required' => 'Falta inválida.',
                'integer'  => 'Falta inválida.',
            ],
        ];
    }
}

