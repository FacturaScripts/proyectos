<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
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
namespace FacturaScripts\Plugins\Proyectos\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Description of EstadoProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EstadoProyecto extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var bool
     */
    public $editable;

    /**
     *
     * @var integer
     */
    public $idestado;

    /**
     *
     * @var string
     */
    public $nombre;

    /**
     *
     * @var bool
     */
    public $predeterminado;

    public function clear()
    {
        parent::clear();
        $this->editable = true;
        $this->predeterminado = false;
    }

    /**
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idestado';
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
        if (isset($this->predeterminado)) {
            $this->ResetProjectDefault();
        }

        return parent::save();
    }

    /**
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'proyectos_estados';
    }

    /**
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'AdminProyectos?activetab=List'): string
    {
        return parent::url('list', $list);
    }

    /**
     * Set a single default state
     */
    protected function ResetProjectDefault()
    {
        $where = [
            new DataBaseWhere('predeterminado', true),
            new DataBaseWhere('idestado', $this->idestado, '!=')
        ];
        foreach ($this->all($where) as $status) {
            $status->predeterminado = false;
            $status->saveUpdate();
        }
    }
}
