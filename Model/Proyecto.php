<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Plugins\Proyectos\Lib\ProjectCodeGenerator;
use FacturaScripts\Dinamic\Lib\ProjectTotalManager;

/**
 * Description of Proyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Proyecto extends Base\ModelOnChangeClass
{
    use Base\ModelTrait;

    /** @var string */
    public $codcliente;

    /** @var string */
    public $descripcion;

    /** @var bool */
    public $editable;

    /** @var string */
    public $fecha;

    /** @var string */
    public $fechafin;

    /** @var string */
    public $fechainicio;

    /** @var int */
    public $idempresa;

    /** @var int */
    public $idestado;

    /** @var int */
    public $idproyecto;

    /** @var string */
    public $nick;

    /** @var string */
    public $nombre;

    /** @var bool */
    public $privado;

    /** @var float */
    public $totalcompras;

    /** @var float */
    public $totalpendientefacturar;

    /** @var float */
    public $totalventas;

    public function clear()
    {
        parent::clear();
        $this->editable = true;
        $this->fecha = Tools::date();
        $this->idempresa = Tools::settings('default', 'idempresa', 1);
        $this->privado = false;
        $this->totalcompras = 0.0;
        $this->totalpendientefacturar = 0.0;
        $this->totalventas = 0.0;
        $this->nick = Session::user()->nick;

        // select default status
        foreach ($this->getAvailableStatus() as $status) {
            if ($status->predeterminado) {
                $this->editable = $status->editable;
                $this->idestado = $status->idestado;
                break;
            }
        }
    }

    public function codeModelSearch(string $query, string $fieldCode = '', array $where = []): array
    {
        $field = empty($fieldCode) ? static::primaryColumn() : $fieldCode;
        $fields = $field . '|nombre|descripcion';
        $where[] = new DataBaseWhere($fields, mb_strtolower($query, 'UTF8'), 'LIKE');
        return CodeModel::all(static::tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * @return EstadoProyecto[]
     */
    public function getAvailableStatus(): array
    {
        $available = [];
        $statusModel = new EstadoProyecto();
        foreach ($statusModel->all([], [], 0, 0) as $status) {
            $available[] = $status;
        }

        return $available;
    }

    /**
     * @return EstadoProyecto
     */
    public function getStatus()
    {
        $status = new EstadoProyecto();
        $status->loadFromCode($this->idestado);
        return $status;
    }

    /**
     * @return TareaProyecto[]
     */
    public function getTasks(): array
    {
        $task = new TareaProyecto();
        $where = [new DataBaseWhere('idproyecto', $this->idproyecto)];
        return $task->all($where, [], 0, 0);
    }

    public function install(): string
    {
        // needed dependencies
        new EstadoProyecto();
        new Cliente();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idproyecto';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'proyectos';
    }

    public function test(): bool
    {
        if (empty($this->nombre)) {
            ProjectCodeGenerator::new($this);
        }

        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->nombre = Tools::noHtml($this->nombre);

        return parent::test();
    }

    /**
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

    protected function setPreviousData(array $fields = [])
    {
        parent::setPreviousData(array_merge(['idestado'], $fields));
	}

	public function parentsave()
	{
        return parent::save();
	}

    public function save(): bool
    {
		ProjectTotalManager::recalculate($this->idproyecto, false, $this);
        return parent::save();
    }
}
