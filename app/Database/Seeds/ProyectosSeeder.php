<?php
// app/Database/Seeds/ProyectosSeeder.php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProyectosSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $by  = 'system:seed';

        $data = [
            ['nombre' => 'COFARMA BODEGA',                 'codigo' => 'PRJ-001', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'PLANTA BARRANQUILLA',            'codigo' => 'PRJ-002', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS CARTAGENA',                'codigo' => 'PRJ-003', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS DUITAMA',                  'codigo' => 'PRJ-004', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS MONTERÍA',                 'codigo' => 'PRJ-005', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'PLANTA NORTE',                   'codigo' => 'PRJ-006', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS SUR',                      'codigo' => 'PRJ-007', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS TUNJA',                    'codigo' => 'PRJ-008', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS SANTA MARTA',              'codigo' => 'PRJ-009', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS VALLEDUPAR',               'codigo' => 'PRJ-010', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS VILLAVICENCIO',            'codigo' => 'PRJ-011', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'CEDIS YOPAL',                    'codigo' => 'PRJ-012', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'COFARMA',                        'codigo' => 'PRJ-013', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'COMERCIAL NUTRESA BOGOTÁ',       'codigo' => 'PRJ-014', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'COMERCIAL NUTRESA DUITAMA',      'codigo' => 'PRJ-015', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'COMERCIAL NUTRESA VILLAVICENCIO','codigo' => 'PRJ-016', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'COMERCIAL NUTRESA YOPAL',        'codigo' => 'PRJ-017', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'NACIONAL DE CHOCOLATES',         'codigo' => 'PRJ-018', 'activo' => 1, 'audit_created_by' => $by, 'audit_updated_by' => $by, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->db->table('tbl_proyectos')->insertBatch($data);
    }
}
