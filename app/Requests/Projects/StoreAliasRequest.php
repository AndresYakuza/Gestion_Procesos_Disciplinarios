<?php
namespace App\Requests\Projects;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Validation\Validation;

class StoreAliasRequest
{
    private static function norm(?string $s): ?string
    {
        if ($s === null) return null;
        $s = mb_strtoupper(trim($s),'UTF-8');
        $s = strtr($s,['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
                       'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N']);
        $s = preg_replace('/[^A-Z0-9 ]+/',' ',$s);
        $s = preg_replace('/\s+/',' ',$s);
        $s = trim($s);
        return $s ?: null;
    }

    public static function rules(): array
    {
        return [
            'proyecto_id' => 'required|is_natural_no_zero',
            'alias'       => 'required|max_length[200]',
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
        $data['alias_norm'] = self::norm($data['alias']);
        if (!$data['alias_norm']) {
            throw new \InvalidArgumentException('Alias no válido');
        }
        return $data;
    }
}
