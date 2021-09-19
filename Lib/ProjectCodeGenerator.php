<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

/**
 * Description of ProjectCodeGenerator
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <daniel.fernandez@athosonline.com>
 */
class ProjectCodeGenerator
{

    /**
     * @param Proyecto $project
     */
    public static function new(&$project)
    {
        $patron = AppSettings::get('proyectos', 'patron', 'PR-{ANYO}-{NUM}');
        $longnumero = AppSettings::get('proyectos', 'longnumero', 6);

        $proyecto = new Proyecto();
        $numero = 1 + $proyecto->count();

        $project->nombre = strtr($patron, [
            '{ANYO}' => date('Y'),
            '{ANYO2}' => date('y'),
            '{MES}' => date('m'),
            '{DIA}' => date('d'),
            '{NUM}' => (string)$numero,
            '{0NUM}' => str_pad((string)$numero, $longnumero, '0', STR_PAD_LEFT)
        ]);
    }
}
