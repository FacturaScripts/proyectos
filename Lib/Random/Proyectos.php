<?php
/**
 * This file is part of Randomizer plugin for FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\Proyectos\Lib\Random;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Plugins\Randomizer\Lib\Random\NewItems;
use FacturaScripts\Plugins\Proyectos\Model\EstadoProyecto;
use FacturaScripts\Plugins\Proyectos\Model\FaseTarea;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Plugins\Proyectos\Model\TareaProyecto;
use FacturaScripts\Plugins\Proyectos\Model\UserProyecto;
use Faker;

/**
 * Description of Proyectos
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello  <yopli2000@gmail.com>
 */
class Proyectos extends NewItems
{

    /**
     *
     * @var AlbaranProveedor[]
     */
    protected static $deliveryNotes = null;

    /**
     *
     * @var PresupuestoProveedor[];
     */
    protected static $estimations = null;

    /**
     *
     * @var FacturaProveedor[]
     */
    protected static $invoices = null;

    /**
     *
     * @var PedidoProveedor[]
     */
    protected static $orders = null;

    /**
     *
     * @var FaseTarea[]
     */
    protected static $phases = null;

    /**
     *
     * @var EstadoProyecto[]
     */
    protected static $status = null;

    /**
     *
     * @param int $number
     * @return int
     */
    public static function create(int $number = 50): int
    {
        $faker = Faker\Factory::create('es_ES');

        for ($generated = 0; $generated < $number; $generated++) {
            $project = new Proyecto();
            $project->codcliente = static::codcliente();
            $project->idempresa = static::empresa();
            $project->nick = static::nick();
            $project->descripcion = $faker->optional()->text;
            $project->nombre = $faker->name();
            $project->fecha = $faker->date();
            $project->fechainicio = $faker->optional(0.1)->date();
            $project->fechafin = $faker->optional(0.1)->date();
            $project->privado = $faker->optional()->boolean();

            $estado = static::projectStatus();
            $project->idestado = $estado->idestado;
            $project->editable = $estado->editable;

            if ($project->exists()) {
                continue;
            }

            if (false === $project->save()) {
                break;
            }

            if ($project->privado) {
                static::createUserProject($faker, $project->idproyecto);
            }

            static::createTask($faker, $project->idproyecto);
            static::assignToPurchases($faker, $project->idproyecto);
        }

        return $generated;
    }

    /**
     * Assign the project to a random number of purchase documents.
     *
     * @param Faker $faker
     * @param int $code
     */
    protected static function assignToPurchases(&$faker, $code)
    {
        static::loadPurchasesDocs();
        static::setProjectToDocument($code, static::$estimations, $faker->numberBetween(1, 10));
        static::setProjectToDocument($code, static::$orders, $faker->numberBetween(1, 10));
        static::setProjectToDocument($code, static::$deliveryNotes, $faker->numberBetween(1, 10));
        static::setProjectToDocument($code, static::$invoices, $faker->numberBetween(1, 10));
    }

    /**
     * Create tasks for the project.
     *
     * @param Faker $faker
     * @param int $code
     */
    protected static function createTask(&$faker, $code)
    {
        $max = $faker->numberBetween(1, 10);
        for ($index = 1; $index <= $max; $index++) {
            $task = new TareaProyecto();
            $task->idproyecto = $code;
            $task->nombre = $faker->name();
            $task->fecha = $faker->date();
            $task->fechainicio = $faker->optional(0.1)->date();
            $task->fechafin = $faker->optional(0.1)->date();
            $task->cantidad = $faker->numberBetween(1, 99);
            $task->descripcion = $faker->text;
            $task->idfase = static::taskPhase();
            if (false === $task->save()) {
                break;
            }
        }
    }

    /**
     * Create user access to private projects.
     *
     * @param Faker $faker
     * @param int $code
     */
    protected static function createUserProject(&$faker, $code)
    {
        $max = $faker->numberBetween(1, 3);
        for ($index = 1; $index <= $max; $index++) {
            $nick = static::nick();
            $where = [
                new DataBaseWhere('idproyecto', $code),
                new DataBaseWhere('nick', $nick),
            ];

            $user = new UserProyecto();
            if ($user->loadFromCode('', $where)) {
                break;
            }

            $user->idproyecto = $code;
            $user->nick = $nick;
            $user->fecha = $faker->date();

            if (false === $user->save()) {
                break;
            }
        }
    }

    /**
     * Upload the list of purchase documents without assigning to any project.
     */
    protected static function loadPurchasesDocs()
    {
        $where = [ new DataBaseWhere('idproyecto', NULL, 'IS') ];
        if (null === static::$estimations) {
            $estimation = new PresupuestoProveedor();
            static::$estimations = $estimation->all($where);
        }

        if (null === static::$orders) {
            $order = new PedidoProveedor();
            static::$orders = $order->all($where);
        }

        if (null === static::$deliveryNotes) {
            $deliveryNote = new AlbaranProveedor();
            static::$deliveryNotes = $deliveryNote->all($where);
        }

        if (null === static::$invoices) {
            $invoice = new FacturaProveedor();
            static::$invoices = $invoice->all($where);
        }
    }

    /**
     * Returns a random status for a project.
     *
     * @return EstadoProyecto
     */
    protected static function projectStatus()
    {
        if (null === static::$status) {
            $projectStatus = new EstadoProyecto();
            static::$status = $projectStatus->all();
        }

        \shuffle(static::$status);
        return empty(static::$status) ? $projectStatus : static::$status[0];
    }

    /**
     * Returns a random phase for a task.
     *
     * @return int
     */
    protected static function taskPhase()
    {
        if (null === static::$phases) {
            $taskPhase = new FaseTarea();
            static::$phases = $taskPhase->all();
        }

        \shuffle(static::$phases);
        return static::$phases[0]->idfase;
    }

    /**
     * Assign a project to a document from the indicated document list.
     * If you manage to assign the project, the assigned document is removed
     * from the document list.
     *
     * @param int $code
     * @param BusinessDocument $docList
     * @param int $max
     */
    protected static function setProjectToDocument($code, &$docList, $max)
    {
        for ($index = 1; $index <= $max; $index++) {
            if (empty($docList)) {
                break;
            }

            \shuffle($docList);
            $docList[0]->idproyecto = $code;
            if (false === $docList[0]->save()) {
                break;
            }
            \array_splice($docList, 0, 1);
        }
    }
}
