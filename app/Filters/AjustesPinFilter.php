<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AjustesPinFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ($session->get('ajustesPinOk') === true) {
            return;
        }

        $uri  = service('uri');
        $path = trim($uri->getPath(), '/');

        // No bloquear las rutas de acceso/salida del PIN
        if (str_starts_with($path, 'ajustes/acceso') || str_starts_with($path, 'ajustes/salir')) {
            return;
        }

        $session->set('ajustes_redirect', current_url());

        return redirect()->to(site_url('ajustes/acceso'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}


