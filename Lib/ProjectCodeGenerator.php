<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of ProjectCodeGenerator
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <daniel.fernandez@athosonline.com>
 */
class ProjectCodeGenerator
{
    /**
     * 
     * @param type $project
     */
    public static function new(&$project)
    {
        $proyectopatron = static::toolBox()->appSettings()->get('proyectos', 'proyectopatron');
        $proyectolongnumero = static::toolBox()->appSettings()->get('proyectos', 'proyectolongnumero');
        
        $patron = \strtr($proyectopatron, [
            '{ANYO}' => date('Y'),
            '{ANYO2}' => date('y'),
            '{MES}' => date('m'),
            '{DIA}' => date('d'),
        ]);
        
        $proyecto = new Proyecto();
        $nombre = \strtr($patron, [
            '{NUM}' => '',
            '{0NUM}' => '',
        ]);
        $where = [new DataBaseWhere('nombre', $nombre, 'LIKE')];
        $numero = $proyecto->count($where) + 1;
        
        $patron = \strtr($patron, [
            '{NUM}' => $numero,
            '{0NUM}' => \str_pad($numero, $proyectolongnumero, '0', \STR_PAD_LEFT)
        ]);
        
        $project->nombre = $patron;
    }
    
    /**
     * 
     * @return ToolBox
     */
    protected static function toolBox()
    {
        return new ToolBox();
    }
}