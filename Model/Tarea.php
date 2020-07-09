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
namespace FacturaScripts\Plugins\Proyectos\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Plugins\Proyectos\Model\EstadoProyecto;
use FacturaScripts\Plugins\Proyectos\Model\FaseTarea;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of Tarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class Tarea extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    /**
     *
     * @var int
     */
    public $cantidad;

    /**
     *
     * @var int
     */
    public $idproyecto;

    /**
     *
     * @var int
     */
    public $idtarea;

    /**
     *
     * @var int
     */
    public $idfase;

    /**
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var date
     */
    public $fecha;

    /**
     *
     * @var date
     */
    public $fechafin;

    /**
     *
     * @var date
     */
    public $fechainicio;

    /**
     *
     * @var string
     */
    public $nombre;

    public function clear()
    {
        parent::clear();
        $this->fecha = \date(self::DATE_STYLE);

        /// select default status
        foreach ($this->getAvaliablePhases() as $status) {
            if ($status->predeterminado) {
                $this->idfase = $status->idfase;
                break;
            }
        }
    }

    /**
     * 
     * @return FaseTarea[]
     */
    public function getAvaliablePhases()
    {
        $avaliable = [];
        $statusModel = new FaseTarea();
        foreach ($statusModel->all([], [], 0, 0) as $status) {
            $avaliable[] = $status;
        }

        return $avaliable;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Proyecto();
        new FaseTarea();

        return parent::install();
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idtarea';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        $result = parent::save();

        $phase = new FaseTarea();
        $phase->loadFromCode($this->idfase);

        $project = new Proyecto();
        $project->loadFromCode($this->idproyecto);

        if ($phase->predeterminado) {
            $this->setDefaultStatusProject($project);
        } else {
            if (!is_null($phase->idestado)) {
                if (!is_null($phase->tipo)) {
                    $this->phaseTypes($phase, $project);
                } else {
                    $project->idestado = $phase->idestado;
                    $project->save();
                }
            }
        }

        return $result;
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'tareas';
    }

    /**
     * If the project is completed or canceled and a new task is added,
     * then it sets the default project status
     * 
     * @param string $project
     */
    public function setDefaultStatusProject($project)
    {
        $StatusProject = new EstadoProyecto();
        $where = [new DataBaseWhere('predeterminado', true)];
        $StatusProject->loadFromCode('', $where);

        if ($StatusProject) {
            $PhaseTask = new FaseTarea();
            $where = [new DataBaseWhere('idestado', $project->idestado)];
            $PhaseTask->loadFromCode('', $where);

            if ($PhaseTask) {
                if (!is_null($PhaseTask->tipo)) {
                    $project->idestado = $StatusProject->idestado;
                    $project->save();
                }
            }
        }
    }

    /**
     * We ask if all tasks in a project are completed or canceled.
     * If correct, we mark the status of the project with the linked phase
     * 
     * @param FaseTarea $phase
     * @param Proyecto  $project
     */
    public function phaseTypes($phase, $project)
    {
        $modelTasks = new Tarea();
        $where = [new DataBaseWhere('idproyecto', $project->idproyecto)];
        $tasks = $modelTasks->all($where);

        $status = true;
        foreach ($tasks as $task) {
            if ($task->idfase !== $phase->idfase) {
                $status = false;
            }
        }

        if ($status) {
            $project->idestado = $phase->idestado;
            $project->save();
        } else {
            $this->combineTypePhases($project, $tasks);
        }
    }

    /**
     * We ask if the sum of the completed and canceled tasks is
     * equal to the total project tasks to complete it.
     * 
     * @param Proyecto $project
     * @param Tarea[]  $tasks
     */
    public function combineTypePhases($project, $tasks)
    {
        $typeComplete = 0;
        $typeReject = 0;

        $phase = new FaseTarea();

        foreach ($tasks as $task) {
            $phase->clear();
            $phase->loadFromCode($task->idfase);

            if ($phase->tipo === 0) {
                $typeComplete++;
            } else if ($phase->tipo === 1) {
                $typeReject++;
            }
        }

        if (($typeComplete + $typeReject) == count($tasks)) {
            $phase->clear();
            $where = [new DataBaseWhere('tipo', 0)];
            $phase->loadFromCode('', $where);

            if ($phase) {
                $project->idestado = $phase->idestado;
                $project->save();
            }
        }
    }
}
