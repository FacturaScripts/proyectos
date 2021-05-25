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
namespace FacturaScripts\Plugins\Proyectos\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\Proyectos\Lib\ProjectCodeGenerator;

/**
 * Description of Proyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Proyecto extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $codcliente;

    /**
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var bool
     */
    public $editable;

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
     * @var integer
     */
    public $idempresa;

    /**
     *
     * @var integer
     */
    public $idestado;

    /**
     *
     * @var integer
     */
    public $idproyecto;

    /**
     *
     * @var string
     */
    public $nick;

    /**
     *
     * @var string
     */
    public $nombre;

    /**
     *
     * @var bool
     */
    public $privado;

    /**
     * 
     * @var float
     */
    public $totalcompras;

    /**
     * 
     * @var float
     */
    public $totalventas;

    public function clear()
    {
        parent::clear();
        $this->editable = true;
        $this->fecha = \date(self::DATE_STYLE);
        $this->privado = false;
        $this->totalcompras = 0.0;
        $this->totalventas = 0.0;

        /// select default status
        foreach ($this->getAvaliableStatus() as $status) {
            if ($status->predeterminado) {
                $this->editable = $status->editable;
                $this->idestado = $status->idestado;
                break;
            }
        }
    }

    /**
     * 
     * @return EstadoProyecto[]
     */
    public function getAvaliableStatus()
    {
        $avaliable = [];
        $statusModel = new EstadoProyecto();
        foreach ($statusModel->all([], [], 0, 0) as $status) {
            $avaliable[] = $status;
        }

        return $avaliable;
    }

    /**
     * 
     * @return EstadoProyecto
     */
    public function getStatus()
    {
        $status = new EstadoProyecto();
        $status->loadFromCode($this->idestado);
        return $status;
    }

    /**
     * 
     * @return Tarea[]
     */
    public function getTasks()
    {
        $task = new TareaProyecto();
        $where = [new DataBaseWhere('idproyecto', $this->idproyecto)];
        return $task->all($where, [], 0, 0);
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new EstadoProyecto();
        new Cliente();

        return parent::install();
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idproyecto';
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
     * @return string
     */
    public static function tableName(): string
    {
        return 'proyectos';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (empty($this->nombre)) {
            ProjectCodeGenerator::new($this);
        }

        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);
        return parent::test();
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'idestado':
                $this->editable = $this->getStatus()->editable;
                return true;
        }

        return parent::onChange($field);
    }

    /**
     * 
     * @param User $user
     *
     * @return bool
     */
    public function userCanSee($user): bool
    {
        if ($user->admin || false === $this->privado || $this->nick === $user->nick) {
            return true;
        }

        $userProject = new UserProyecto();
        $where = [
            new DataBaseWhere('idproyecto', $this->idproyecto),
            new DataBaseWhere('nick', $user->nick)
        ];
        return $userProject->loadFromCode('', $where);
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        parent::setPreviousData(\array_merge(['idestado'], $fields));
    }
}
