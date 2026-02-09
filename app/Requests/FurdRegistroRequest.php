<?php namespace App\Requests;

class FurdRegistroRequest
{
    public static function rules(): array
    {
        return [
            'cedula'          => 'required|min_length[5]|max_length[30]',
            'fecha_evento'    => 'required|valid_date[Y-m-d]',
            'hora'            => 'required',
            'superior'        => 'required|max_length[60]',
            'hecho'           => 'required|min_length[5]|max_length[5000]|max_word_length[120]',

            'correo'          => 'permit_empty|valid_email|max_length[150]',
            'correo_cliente'  => 'permit_empty|valid_email|max_length[150]',
            'empresa_usuaria' => 'required|max_length[180]',
            'nombre_completo' => 'required|max_length[180]',
            'expedida_en'     => 'required|max_length[120]',

            'faltas'          => 'required',
            'faltas.*'        => 'required|integer',

            'evidencias' => [
                'permit_empty',
                'max_size[evidencias,25600]',
                'ext_in[evidencias,pdf,jpg,jpeg,png,heic,doc,docx,xlsx,xls,mp4,mov,avi,mkv,webm]',
            ],
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
                'max_length' => 'El hecho es demasiado largo (máximo 5000 caracteres).',
                'max_word_length' => 'El hecho contiene una palabra demasiado larga sin espacios. Divide el texto en frases o agrega espacios.',
            ],
            'correo' => [
                'valid_email' => 'El correo no es válido.',
            ],
            'correo_cliente' => [
                'valid_email' => 'El correo del cliente no es válido.',
                'max_length'  => 'El correo del cliente es demasiado largo.',
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
            'evidencias' => [
                'max_size' => 'Cada archivo debe ser menor o igual a 25 MB.',
                'ext_in'   => 'Formato de archivo no permitido. Use PDF, imágenes, Office o videos autorizados.',
            ],

        ];
    }
}

