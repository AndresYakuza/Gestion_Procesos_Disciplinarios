<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pager extends BaseConfig
{
    public array $templates = [
        'default_full'   => 'CodeIgniter\Pager\Views\default_full',
        'default_simple' => 'CodeIgniter\Pager\Views\default_simple',
        'bootstrap'      => 'CodeIgniter\Pager\Views\bootstrap',
        'bootstrap_full' => 'pagers/bootstrap_full',
    ];

    public int $perPage = 20;
}
