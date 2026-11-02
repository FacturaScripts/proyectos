<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Where;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Lib\ProjectCodeGenerator;

/**
 * Description of Proyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Proyecto extends ModelClass
{
    use ModelTrait;

    /** @var string Código del cliente asociado al proyecto. */
    public $codcliente;

    /** @var string Descripción del proyecto. */
    public $descripcion;

    /** @var bool Indica si el proyecto se puede editar. */
    public $editable;

    /** @var string Fecha de creación del proyecto. */
    public $fecha;

    /** @var string Fecha de finalización del proyecto. */
    public $fechafin;

    /** @var string Fecha de inicio del proyecto. */
    public $fechainicio;

    /** @var int Identificador de la empresa. */
    public $idempresa;

    /** @var int Identificador del estado actual del proyecto. */
    public $idestado;

    /** @var int Identificador del proyecto. */
    public $idproyecto;

    /** @var string Nick del usuario que crea el proyecto. */
    public $nick;

    /** @var string Nombre del proyecto. */
    public $nombre;

    /** @var bool Indica si el proyecto es privado y solo lo pueden ver los usuarios asignados. */
    public $privado;

    /** @var float Total de compras asociadas al proyecto. */
    public $totalcompras;

    /** @var float Total pendiente de facturar del proyecto. */
    public $totalpendientefacturar;

    /** @var float Total de ventas asociadas al proyecto. */
    public $totalventas;

    public function clear(): void
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
        $where[] = Where::like($fields, mb_strtolower($query, 'UTF8'));
        return CodeModel::all(static::tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * @return EstadoProyecto[]
     */
    public function getAvailableStatus(): array
    {
        $available = [];
        foreach (EstadoProyecto::all([], [], 0, 0) as $status) {
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
        $status->load($this->idestado);
        return $status;
    }

    /**
     * @return TareaProyecto[]
     */
    public function getTasks(): array
    {
        $where = [Where::eq('idproyecto', $this->idproyecto)];
        return TareaProyecto::all($where, [], 0, 0);
    }

    public function install(): string
    {
        // needed dependencies
        new EstadoProyecto();
        new Empresa();
        new User();
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

        // sincronizamos editable con el estado seleccionado, tanto en el alta como en la edición
        // (onChange sólo se dispara al actualizar, así que en el alta no se ejecutaría)
        $this->editable = $this->getStatus()->editable;

        return parent::test();
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function onChange(string $field): bool
    {
        switch ($field) {
            case 'idestado':
                $this->editable = $this->getStatus()->editable;
                return true;
        }

        return parent::onChange($field);
    }

    /**
     * Devuelve la consulta con los identificadores de los proyectos de la empresa del usuario.
     * Si el usuario no tiene empresa asignada, devuelve todos los proyectos públicos.
     *
     * @param User $user Usuario que consulta.
     * @param DataBase $db Conexión usada para escapar los valores.
     *
     * @return string
     */
    public static function companyProjectsSql(User $user, DataBase $db): string
    {
        // con una sola empresa el formulario de usuario oculta ese campo y queda vacío
        if (empty($user->idempresa)) {
            return 'SELECT idproyecto FROM proyectos WHERE privado = ' . $db->var2str(false);
        }

        return 'SELECT idproyecto FROM proyectos WHERE idempresa = ' . $db->var2str($user->idempresa);
    }

    /**
     * Devuelve las condiciones para listar los proyectos públicos visibles por el usuario.
     * Solo añade el filtro de empresa cuando el usuario tiene una asignada.
     *
     * @param User $user Usuario que consulta.
     * @param string $prefix Prefijo de tabla de las columnas, por ejemplo 'proyectos.'.
     *
     * @return Where[]
     */
    public static function publicWhere(User $user, string $prefix = ''): array
    {
        $where = [Where::eq($prefix . 'privado', false)];
        if (false === empty($user->idempresa)) {
            $where[] = Where::eq($prefix . 'idempresa', $user->idempresa);
        }

        return $where;
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
            Where::eq('idproyecto', $this->idproyecto),
            Where::eq('nick', $user->nick)
        ];
        return $userProject->loadWhere($where);
    }
}
