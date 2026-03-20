<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Gpd extends BaseConfig
{
    public string $correoGestionProcesos;

    public function __construct()
    {
        parent::__construct();

        $this->correoGestionProcesos = (string) env('gpd.correoGestionProcesos', '');
    }
}