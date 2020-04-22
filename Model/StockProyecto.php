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
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of StockProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class StockProyecto extends Base\ModelClass
{

    use Base\ModelTrait;

    const MAX_DECIMALS = 3;

    /**
     *
     * @var float
     */
    public $cantidad;

    /**
     *
     * @var float
     */
    public $disponible;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var int
     */
    public $idproducto;

    /**
     *
     * @var int
     */
    public $idproyecto;

    /**
     *
     * @var float
     */
    public $pterecibir;

    /**
     *
     * @var string
     */
    public $referencia;

    /**
     *
     * @var float
     */
    public $reservada;

    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
        $this->disponible = 0.0;
        $this->pterecibir = 0.0;
        $this->reservada = 0.0;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependecies
        new Proyecto();
        new Producto();
        new Variante();

        return parent::install();
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'proyectos_stocks';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->cantidad = \round($this->cantidad, self::MAX_DECIMALS);
        $this->referencia = $this->toolBox()->utils()->noHtml($this->referencia);

        $this->reservada = \round($this->reservada, self::MAX_DECIMALS);
        if ($this->reservada < 0) {
            $this->reservada = 0;
        }

        $this->pterecibir = \round($this->pterecibir, self::MAX_DECIMALS);
        if ($this->pterecibir < 0) {
            $this->pterecibir = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
        return parent::test();
    }
}
