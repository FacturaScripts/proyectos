<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Description of FaseTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class FaseTarea extends ModelClass
{
    use ModelTrait;

    /**
     * @var integer
     */
    public $idestado;

    /**
     * @var integer
     */
    public $idfase;

    /**
     * @var string
     */
    public $nombre;

    /**
     * @var bool
     */
    public $predeterminado;

    /**
     * @var integer
     */
    public $tipo;

    public function clear(): void
    {
        parent::clear();
        $this->predeterminado = false;
    }

    public function delete(): bool
    {
        if($this->predeterminado) {
            Tools::log()->error('cannot-delete-default-state');
            return false;
        }

        return parent::delete();
    }

    public function install(): string
    {
        // needed dependencies
        new EstadoProyecto();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idfase';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public function save(): bool
    {
        // escapamos el html
        $this->nombre = Tools::noHtml($this->nombre);

        if (isset($this->tipo)) {
            $this->resetPhaseType();
        }

        if ($this->predeterminado) {
            $this->resetPhaseDefault();
        }

        return parent::save();
    }

    public static function tableName(): string
    {
        return 'tareas_fases';
    }

    public function url(string $type = 'auto', string $list = 'AdminProyectos?activetab=List'): string
    {
        return parent::url('list', $list);
    }

    /**
     * Set a single phase by default
     */
    protected function resetPhaseDefault(): void
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
    protected function resetPhaseType(): void
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
