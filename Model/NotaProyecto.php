<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base;

/**
 * Description of FaseTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class NotaProyecto extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var integer
     */
    public $idnota;
    
    /**
     *
     * @var integer
     */
    public $idproyecto;

    /**
     *
     * @var integer
     */
    public $idtarea;

    /**
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var datetime
     */
    public $fecha;
    
    /**
     * User id
     * @var string
     */
    public $nick;

    public function clear()
    {
        parent::clear();
        $this->fecha = \date(self::DATETIME_STYLE);
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idnota';
    }

    public static function tableName(): string {
        return 'proyectos_notas';
    }
    
    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        $model = $this->modelClassName();
        switch ($type) {
            case 'list':
                return ($this->idproyecto)?'EditProyecto?code='.$this->idproyecto.'&activetab=ListNotaProyecto':'ListProyecto';

            case 'new':
                return 'Edit' . $model;
        }

        /// default
        return 'Edit' . $model . '?code=' . $this->idnota;
    }
}
