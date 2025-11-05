<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdjuntoModel;

class CitacionController extends BaseController
{
    protected $helpers = ['url', 'form', 'text'];

    /**
     * GET /citacion
     * Muestra el formulario de citación.
     * Si viene ?furd_id=123, precarga los adjuntos de ese FURD.
     */
    public function create(): string
    {
        $furdId   = (int) ($this->request->getGet('furd_id') ?? 0);
        $adjuntos = [];

        if ($furdId > 0) {
            $adjuntos = (new AdjuntoModel())
                ->where(['origen' => 'furd', 'origen_id' => $furdId])
                ->orderBy('id', 'desc')
                ->findAll();
        }

        // Ajusta el nombre de la vista a como la tengas: 'citacion/index' o 'citacion/create'
        return view('citacion/index', [
            'titulo'   => 'Generar citación',
            'furd_id'  => $furdId,
            'adjuntos' => $adjuntos,
        ]);
    }

    /**
     * POST /citacion
     * Procesa el formulario (por ahora stub: valida y redirige con flash).
     * En la siguiente iteración guardamos en BD y generamos PDF.
     */
    public function store()
    {
        $data = $this->request->getPost();

        // Normaliza fecha dd/mm/yyyy → yyyy-mm-dd (si llega así)
        if (!empty($data['fecha']) && preg_match('~^\d{2}/\d{2}/\d{4}$~', $data['fecha'])) {
            [$d, $m, $y] = explode('/', $data['fecha']);
            $data['fecha'] = sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
        // Normaliza hora a HH:MM
        if (!empty($data['hora']) && preg_match('~^\d{1,2}:\d{2}~', $data['hora'])) {
            [$h, $mm] = explode(':', $data['hora']);
            $data['hora'] = sprintf('%02d:%02d', (int) $h, (int) $mm);
        }

        $rules = [
            'consecutivo' => 'permit_empty|max_length[50]',
            'fecha'       => 'required|valid_date[Y-m-d]',
            'hora'        => 'required|regex_match[/^\d{2}:\d{2}$/]',
            'medio'       => 'required|in_list[email,telefono,whatsapp,presencial,otro]',
            'hecho'       => 'required|min_length[10]',
            'furd_id'     => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        // TODO: guardar en BD (CitacionModel) y generar PDF / notificación.
        // $id = (new CitacionModel())->insert([...], true);

        return redirect()->to(site_url('citacion'))
            ->with('success', 'Citación generada (demo). Guardado en BD y PDF se implementará en el siguiente paso.');
    }

    /**
     * GET /citacion/adjuntos/{furdId}
     * Endpoint JSON para que la vista consulte los adjuntos de un FURD.
     */
    public function adjuntosByFurd(int $furdId)
    {
        $rows = (new AdjuntoModel())
            ->where(['origen' => 'furd', 'origen_id' => $furdId])
            ->orderBy('id', 'desc')
            ->findAll();

        // Devuelve lo esencial; ajusta "url" según tu ruta de descarga
        $items = array_map(static function ($r) {
            return [
                'id'           => (int) ($r['id'] ?? 0),
                'nombre'       => $r['nombre_original'] ?? ($r['ruta'] ?? 'archivo'),
                'mime'         => $r['mime'] ?? null,
                'tamano_bytes' => isset($r['tamano_bytes']) ? (int) $r['tamano_bytes'] : null,
                'ruta'         => $r['ruta'] ?? null,
            ];
        }, $rows);

        return $this->response->setJSON(['items' => $items]);
    }
}
