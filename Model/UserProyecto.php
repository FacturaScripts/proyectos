<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;


/**
 * Description of UserProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class UserProyecto extends ModelClass
{

    use ModelTrait;

    /**
     * @var string
     */
    public $fecha;

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $idproyecto;

    /**
     * @var string
     */
    public $nick;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
    }

    public function install(): string
    {
        // needed dependencies
        new Proyecto();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'proyectos_users';
    }
}
