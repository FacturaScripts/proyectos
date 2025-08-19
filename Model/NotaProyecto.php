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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;

/**
 * Description of FaseTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 */
class NotaProyecto extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $descripcion;

    /** @var string */
    public $fecha;

    /** @var integer */
    public $idnota;

    /** @var integer */
    public $idproyecto;

    /** @var integer */
    public $idtarea;

    /** @var string */
    public $nick;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::dateTime();
        $this->nick = Session::user()->nick;
    }

    /**
     * @return Proyecto
     */
    public function getProject()
    {
        $project = new Proyecto();
        $project->load($this->idproyecto);
        return $project;
    }

    public function install(): string
    {
        // needed dependencies
        new TareaProyecto();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idnota';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'idnota';
    }

    public static function tableName(): string
    {
        return 'proyectos_notas';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $type === 'list' && $this->idproyecto ? $this->getProject()->url() . '&activetab=ListNotaProyecto' : parent::url($type, $list);
    }
}
