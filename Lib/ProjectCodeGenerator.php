<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Proyectos\Lib;

use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Core\Where;

/**
 * Description of ProjectCodeGenerator
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <daniel.fernandez@athosonline.com>
 */
class ProjectCodeGenerator
{
    public static function new(Proyecto &$project)
    {
        $patron = Tools::settings('proyectos', 'patron', 'PR-{ANYO}-{NUM}');
        $long_numero = Tools::settings('proyectos', 'longnumero', 6);
        $reset = (bool)Tools::settings('proyectos', 'reiniciar_patron_anualmente', 0);

        $proyecto = new Proyecto();

        $fecha = empty($project->fecha) ? date('Y-m-d') : $project->fecha;
        $timestamp = strtotime($fecha);
        $year = date('Y', $timestamp);
        $year2 = date('y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);

        if (!$reset) {
            // default behaviour: keep existing global counting
            $numero = 1 + $proyecto->count();
        } else {
            // build prefix replacing date placeholders but leaving numeric placeholders empty
            $replacements = [
                '{ANYO}' => $year,
                '{ANYO2}' => $year2,
                '{MES}' => $month,
                '{DIA}' => $day,
                '{NUM}' => '',
                '{0NUM}' => ''
            ];

            $prefix = strtr($patron, $replacements);

            // find projects that start with the same prefix and belong to the same company, limited to current year
            $where = [
                Where::like('nombre', $prefix . '%'),
                Where::eq('idempresa', $project->idempresa),
                Where::gte('fecha', $year . '-01-01'),
                Where::lte('fecha', $year . '-12-31')
            ];
            $projects = Proyecto::all($where, [], 0, 0);

            $max = 0;
            foreach ($projects as $p) {
                if (empty($p->fecha)) {
                    continue;
                }
                $pYear = date('Y', strtotime($p->fecha));
                if ($pYear !== $year) {
                    continue;
                }
                if (preg_match('/(\\d+)$/', $p->nombre, $m)) {
                    $num = intval($m[1]);
                    if ($num > $max) {
                        $max = $num;
                    }
                }
            }

            $numero = $max + 1;
        }

        $project->nombre = strtr($patron, [
            '{ANYO}' => $year,
            '{ANYO2}' => $year2,
            '{MES}' => $month,
            '{DIA}' => $day,
            '{NUM}' => (string)$numero,
            '{0NUM}' => str_pad((string)$numero, $long_numero, '0', STR_PAD_LEFT)
        ]);
    }
}
