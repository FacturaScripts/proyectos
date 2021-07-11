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
 * Description of FaseTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class FaseTarea extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var integer
     */
    public $idestado;

    /**
     *
     * @var integer
     */
    public $idfase;

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

    /**
     *
     * @var integer
     */
    public $tipo;

    public function clear()
    {
        parent::clear();
        $this->predeterminado = false;
    }

    /**
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new EstadoProyecto();

        return parent::install();
    }

    /**
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idfase';
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
        if (isset($this->tipo)) {
            $this->ResetPhaseType();
        }

        if (isset($this->predeterminado)) {
            $this->ResetPhaseDefault();
        }

        return parent::save();
    }

    /**
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'tareas_fases';
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
     * Set a single phase by default
     */
    protected function ResetPhaseDefault()
    {
        $where = [
            new DataBaseWhere('predeterminado', true),
            new DataBaseWhere('idfase', $this->idfase, '!=')
        ];
        foreach ($this->all($where) as $phase) {
            $phase->predeterminado = false;
            $phase->saveUpdate();
        }
    }

    /**
     * Set only one type of phase at a time
     */
    protected function ResetPhaseType()
    {
        $where = [
            new DataBaseWhere('tipo', $this->tipo),
            new DataBaseWhere('idfase', $this->idfase, '!=')
        ];
        foreach ($this->all($where) as $phase) {
            $phase->tipo = null;
            $phase->saveUpdate();
        }
    }
}
