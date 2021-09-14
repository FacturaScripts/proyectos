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
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Plugins\Proyectos\Model\FaseTarea;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Plugins\Proyectos\Model\TareaProyecto;
use FacturaScripts\Plugins\Proyectos\Model\UserProyecto;
use FacturaScripts\Plugins\Randomizer\Lib\Random\NewItems;
use Faker;

/**
 * Description of Proyectos
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello <yopli2000@gmail.com>
 */
class Proyectos extends NewItems
{

    /**
     * @var FaseTarea[]
     */
    private static $phases = null;

    /**
     * @param int $number
     *
     * @return int
     */
    public static function create(int $number = 25): int
    {
        $faker = Faker\Factory::create('es_ES');

        static::dataBase()->beginTransaction();
        for ($generated = 0; $generated < $number; $generated++) {
            $project = new Proyecto();
            $project->codcliente = static::codcliente();
            $project->descripcion = $faker->optional()->text;
            $project->fecha = $faker->date();
            $project->fechafin = $faker->optional(0.1)->date();
            $project->fechainicio = $faker->optional(0.1)->date();
            $project->idempresa = static::idempresa();
            $project->nick = static::nick();
            $project->nombre = $faker->name();
            $project->privado = $faker->boolean();

            if ($project->exists()) {
                continue;
            }

            if (false === $project->save()) {
                break;
            }

            if ($project->privado) {
                static::createProjectUsers($faker, $project);
            }

            static::assignDocuments(new PresupuestoProveedor(), $project->idproyecto);
            static::assignDocuments(new PedidoProveedor(), $project->idproyecto);
            static::assignDocuments(new AlbaranProveedor(), $project->idproyecto);
            static::assignDocuments(new FacturaProveedor(), $project->idproyecto);

            static::assignDocuments(new PresupuestoCliente(), $project->idproyecto);
            static::assignDocuments(new PedidoCliente(), $project->idproyecto);
            static::assignDocuments(new AlbaranCliente(), $project->idproyecto);
            static::assignDocuments(new FacturaCliente(), $project->idproyecto);

            static::createTasks($faker, $project->idproyecto);
        }

        static::dataBase()->commit();
        return $generated;
    }

    /**
     * Assign the project to a random number of documents.
     *
     * @param BusinessDocument $model
     * @param int $code
     */
    protected static function assignDocuments($model, $code)
    {
        $limit = mt_rand(0, 9) === 0 ? mt_rand(1, 59) : mt_rand(0, 2);
        if ($limit <= 0) {
            return;
        }

        $where = [
            new DataBaseWhere('editable', true),
            new DataBaseWhere('idproyecto', null, 'IS')
        ];
        foreach ($model->all($where, [], 0, $limit) as $doc) {
            $doc->idproyecto = $code;
            $doc->save();
        }
    }

    /**
     * Create tasks for the project.
     *
     * @param Faker\Generator $faker
     * @param int $code
     */
    protected static function createTasks(&$faker, $code)
    {
        $max = $faker->numberBetween(-1, 10);
        for ($index = 1; $index <= $max; $index++) {
            $task = new TareaProyecto();
            $task->cantidad = $faker->numberBetween(1, 99);
            $task->descripcion = $faker->text;
            $task->fecha = $faker->date();
            $task->fechafin = $faker->optional(0.1)->date();
            $task->fechainicio = $faker->optional(0.1)->date();
            $task->idfase = static::idfase();
            $task->idproyecto = $code;
            $task->nombre = $faker->name();
            if (false === $task->save()) {
                break;
            }
        }
    }

    /**
     * Create user access to private projects.
     *
     * @param Faker\Generator $faker
     * @param Proyecto $project
     */
    protected static function createProjectUsers(&$faker, &$project)
    {
        $max = $faker->numberBetween(-1, 4);
        for ($index = 1; $index <= $max; $index++) {
            $nick = static::nick();
            if ($nick === $project->nick) {
                continue;
            }

            $user = new UserProyecto();
            $where = [
                new DataBaseWhere('idproyecto', $project->idproyecto),
                new DataBaseWhere('nick', $nick)
            ];
            if ($user->loadFromCode('', $where)) {
                break;
            }

            $user->fecha = $faker->date();
            $user->idproyecto = $project->idproyecto;
            $user->nick = $nick;
            if (false === $user->save()) {
                break;
            }
        }
    }

    /**
     * Returns a random phase for a task.
     *
     * @return int
     */
    protected static function idfase()
    {
        if (null === self::$phases) {
            $taskPhase = new FaseTarea();
            self::$phases = $taskPhase->all();
        }

        shuffle(self::$phases);
        return self::$phases[0]->idfase;
    }
}
