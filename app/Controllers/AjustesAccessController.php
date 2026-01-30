<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AjustesAccessController extends BaseController
{
    public function index()
    {
        return view('ajustes/acceso', [
            'error' => session('error'),
        ]);
    }

    public function check()
    {
        $pinIngresado = (string) $this->request->getPost('pin');
        $pinReal      = (string) env('AJUSTES_PIN', '');

        if ($pinReal === '') {
            return redirect()->back()
                ->with('error', 'El PIN de ajustes no está configurado en .env');
        }

        if (hash_equals($pinReal, $pinIngresado)) {
            $session = session();
            $session->set('ajustesPinOk', true);

            $redirectTo = $session->get('ajustes_redirect') ?: site_url('ajustes/faltas');
            $session->remove('ajustes_redirect');

            return redirect()->to($redirectTo);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'PIN incorrecto.');
    }

    public function salir()
    {
        session()->remove('ajustesPinOk');
        return redirect()->to(site_url('/'));
    }
}
