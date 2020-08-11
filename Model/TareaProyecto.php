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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Description of TareaProyecto
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 */
class TareaProyecto extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    const TYPE_COMPLETED = 0;
    const TYPE_PROCESSING = 2;
    const TYPE_CANCELED = 1;

    /**
     *
     * @var int
     */
    public $cantidad;

    /**
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var string
     */
    public $fecha;

    /**
     *
     * @var string
     */
    public $fechafin;

    /**
     *
     * @var string
     */
    public $fechainicio;

    /**
     *
     * @var int
     */
    public $idfase;

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
     * @return FaseTarea
     */
    public function getPhase()
    {
        $phase = new FaseTarea();
        $phase->loadFromCode($this->idfase);
        return $phase;
    }

    /**
     * 
     * @return Proyecto
     */
    public function getProject()
    {
        $project = new Proyecto();
        $project->loadFromCode($this->idproyecto);
        return $project;
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
        if (false === parent::save()) {
            return false;
        }

        $phase = $this->getPhase();
        if ($phase->predeterminado) {
            $this->setDefaultProjectStatus();
            return true;
        }

        if (null === $phase->idestado) {
            return true;
        }

        if (null === $phase->tipo) {
            $project = $this->getProject();
            $project->idestado = $phase->idestado;
            $project->save();
            return true;
        }

        $this->checkOtherTasks($phase);
        return true;
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
     * 
     * @return bool
     */
    public function test()
    {
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);

        return parent::test();
    }

    /**
     * We ask if all tasks in a project are completed or canceled.
     * If correct, we mark the status of the project with the linked phase
     * 
     * @param FaseTarea $phase
     */
    protected function checkOtherTasks($phase)
    {
        $project = $this->getProject();
        $tasks = $project->getTasks();
        foreach ($tasks as $task) {
            if ($task->idfase !== $phase->idfase) {
                $this->deepTaskCheck($project, $tasks);
                return;
            }
        }

        $project->idestado = $phase->idestado;
        $project->save();
    }

    /**
     * We ask if the sum of the completed and canceled tasks is
     * equal to the total project tasks to complete it.
     * 
     * @param Proyecto $project
     * @param Tarea[]  $tasks
     */
    protected function deepTaskCheck($project, $tasks)
    {
        $completed = 0;
        $canceled = 0;
        foreach ($tasks as $task) {
            $phase = $task->getPhase();
            if ($phase->tipo === self::TYPE_COMPLETED) {
                $completed++;
            } elseif ($phase->tipo === self::TYPE_CANCELED) {
                $canceled++;
            }
        }

        if ($completed + $canceled === \count($tasks)) {
            $phase = new FaseTarea();
            $where = [new DataBaseWhere('tipo', self::TYPE_COMPLETED)];
            if ($phase->loadFromCode('', $where)) {
                $project->idestado = $phase->idestado;
                $project->save();
            }
        }
    }

    /**
     * If the project is completed or canceled and a new task is added,
     * then it sets the default project status.
     */
    protected function setDefaultProjectStatus()
    {
        $defaultStatus = new EstadoProyecto();
        $where = [new DataBaseWhere('predeterminado', true)];
        if ($defaultStatus->loadFromCode('', $where)) {
            $project = $this->getProject();
            $project->idestado = $defaultStatus->idestado;
            $project->save();
        }
    }
}
