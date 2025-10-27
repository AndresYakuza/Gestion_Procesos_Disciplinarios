<?php
// app/Database/Seeds/RitFaltasSeeder.php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RitFaltasSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $by  = 'system:seed';

        $descs = [
            'Haber perdido las calidades que lo acreditan para desarrollar la labor contratada o su vigencia (Ej: Licencia de Conducción)',
            'Engañar a la empresa en cuanto a la destinación de los préstamos, permisos otorgados, auxilios o licencias o entregar documentos falsos como soporte',
            'Engañar a la empresa en cuanto a la destinación de las cesantías, siendo estas retiradas total o parcialmente, o entregar documentos falsos como soporte',
            'Permitir que otro trabajador sin permiso de la Empresa utilice el documento Oficial que lo identifica como Trabajador de la Empresa',
            'Realizar alteraciones en el diligenciamiento de registros o informes, reporte de novedades, reporte de producción o cualquier documento, para beneficio propio o de un tercero, o a sabiendas no reportar la alteración',
            'Ejecutar la actividad contratada sin tener en cuenta las guías, protocolos o manual de funciones determinados y usados por la empresa',
            'Dormir Durante la Jornada o turno de trabajo Asignado',
            'Utilizar documento falso para el provecho propio o ajeno',
            'Omitir información relevante antes de ingresar a la empresa',
            'La violación a las normas de seguridad y salud en el trabajo que ponga en peligro los bienes o las personas.',
            'Falta de respeto verbal o física, malos tratos, palabras soeces o sobrenombres, en contra de un compañero de trabajo, superior Jerárquico o miembro de la empresa cliente',
            'Incumplimiento a las órdenes verbales o escritas dadas por los superiores Jerárquicos sin justificación Válida',
            'Ocasionar daño a los equipos o herramientas de trabajo, elementos de protección personal, dotación y/o cualquier otro bien de la empresa o que pertenezca al lugar de trabajo por culpa, mal manejo de la herramienta, falta de diligencia y cuidado',
            'La pérdida de una herramienta de trabajo, elemento de protección personal y dotación, por culpa atribuible al trabajador',
            'Modificar la dotación o colocar aditivos nos autorizados por la empresa o utilizar dotación distinta de la entregada por última vez',
            'Consumir durante o en el área de trabajo, bebidas alcohólicas o sustancias psicoactivas',
            'Consumir producto Final producido por una empresa cliente sin estar autorizado para ello.',
            'Presentarse al lugar de trabajo con aliento Alcohólico o bajo las influencia del Alcohol o Drogas Psicoactivas',
            'Incumplimiento a los deberes establecidos en el ARTÍCULO 75 de este Reglamento cuando no estén regulados expresamente en esta tabla',
            'Incumplimiento a las Obligaciones establecidas en el ARTÍCULO 82 de este Reglamento cuando no estén regulados expresamente en esta tabla',
            'Incumplimiento a las Prohibiciones establecidas en el ARTÍCULO 84 de este Reglamento',
            'Falta no Justificada total al trabajo cuando el trabajador da aviso a la empresa',
            'Falta no Justificada total al trabajo cuando el trabajador no da aviso a la empresa',
            'Retardo Injustificado antes de Iniciar Labores Hasta de 15 minutos',
            'Retiro Injustificado antes de Finalizar el turno de trabajo hasta de 15 minutos',
            'Retardo Injustificado antes de Iniciar Labores por más de 15 minutos Hasta 1 horas',
            'Retiro Injustificado antes de Finalizar el turno de trabajo hasta por 1 horas',
            'Retardo Injustificado antes de Iniciar Labores por más de 1 hora sin permiso o autorización de superior jerárquico',
            'Retiro Injustificado antes de Finalizar el turno de trabajo por más de 1 hora',
            'Cambiar de turno sin autorización de la Empresa',
            'Incumplir las normas de Seguridad y Salud en el Trabajo',
            'Manipular máquina, vehículo o herramienta sin el cumplimiento de los requisitos y calidades establecidos por la empresa',
            'Otorgar permiso a un trabajador para la manipulacion, maquina o herramienta sin el cumplimiento de los requisitos y calidades establecidos por la empresa y sin la autorización',
            'Otorgar autorización a los trabajadores para el desarrollo de labores sin el cumplimiento de los protocolos de seguridad o sin el uso de las herramientas para el trabajo en alturas',
            'Utilizar el tiempo de la alimentación para un objeto diferente del otorgado',
            'Incumplir con los deberes, obligaciones y prohibiciones emanados directamente de la ley cuando no estén regulados en este reglamento y/o en esta tabla',
        ];

        $data = [];
        foreach ($descs as $i => $desc) {
            $data[] = [
                'codigo'           => sprintf('FLT-%03d', $i + 1),
                'descripcion'      => $desc,
                'gravedad'         => 'leve',      // ajustar si tienes clasificación
                'activo'           => 1,
                'audit_created_by' => $by,
                'audit_updated_by' => $by,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        $this->db->table('tbl_rit_faltas')->insertBatch($data);
    }
}
