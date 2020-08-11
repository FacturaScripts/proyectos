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
namespace FacturaScripts\Plugins\Proyectos\Model\ModelView;

use FacturaScripts\Core\Model\Base\ModelView;
use FacturaScripts\Plugins\Proyectos\Model\TareaProyecto as parentTareaProyecto;

/**
 * Description of Tarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class TareaProyecto extends ModelView
{

    public function __construct($data = array())
    {
        parent::__construct($data);
        $this->setMasterModel(new parentTareaProyecto());
    }

    protected function getFields(): array
    {
        return [
            'cantidad' => 'tareas.cantidad',
            'idproyecto' => 'tareas.idproyecto',
            'idtarea' => 'tareas.idtarea',
            'idfase' => 'tareas.idfase',
            'descripcion' => 'tareas.descripcion',
            'fecha' => 'tareas.fecha',
            'fechafin' => 'tareas.fechafin',
            'fechainicio' => 'tareas.fechainicio',
            'nombre' => 'tareas.nombre',
            'idempresa' => 'proyectos.idempresa',
            'privado' => 'proyectos.privado',
            'editable' => 'proyectos.editable',
            'codcliente' => 'proyectos.codcliente'
        ];
    }

    protected function getSQLFrom(): string
    {
        return 'tareas'
            . ' INNER JOIN tareas_fases ON tareas_fases.idfase = tareas.idfase'
            . ' INNER JOIN proyectos ON proyectos.idproyecto = tareas.idproyecto'
            . ' INNER JOIN proyectos_estados ON proyectos_estados.idestado = proyectos.idestado';
    }

    protected function getTables(): array
    {
        return [
            'proyectos',
            'proyectos_estados',
            'tareas',
            'tareas_fases'
        ];
    }
}
