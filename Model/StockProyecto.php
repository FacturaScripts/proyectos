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
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of StockProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class StockProyecto extends ModelClass
{
    use ModelTrait;

    const MAX_DECIMALS = 3;

    /** @var float */
    public $cantidad;

    /** @var float */
    public $disponible;

    /** @var int */
    public $id;

    /** @var int */
    public $idproducto;

    /** @var int */
    public $idproyecto;

    /** @var float */
    public $pterecibir;

    /** @var string */
    public $referencia;

    /** @var float */
    public $reservada;

    public function clear(): void
    {
        parent::clear();
        $this->cantidad = 0.0;
        $this->disponible = 0.0;
        $this->pterecibir = 0.0;
        $this->reservada = 0.0;
    }

    /**
     * @param int $idproyecto
     *
     * @return bool
     */
    public function deleteFromProject($idproyecto)
    {
        $sql = 'DELETE FROM ' . static::tableName() . ' WHERE idproyecto = ' . self::$dataBase->var2str($idproyecto) . ';';
        return self::$dataBase->exec($sql);
    }

    /**
     * @return Variante
     */
    public function getVariant()
    {
        $variant = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variant->loadWhere($where);
        return $variant;
    }

    /**
     * @return Producto
     */
    public function getProduct()
    {
        $product = new Producto();
        $product->load($this->idproducto);
        return $product;
    }

    public function install(): string
    {
        // needed dependencies
        new Proyecto();
        new Variante();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'proyectos_stocks';
    }

    public function test(): bool
    {
        $this->cantidad = round($this->cantidad, self::MAX_DECIMALS);
        $this->referencia = Tools::noHtml($this->referencia);

        $this->reservada = round($this->reservada, self::MAX_DECIMALS);
        if ($this->reservada < 0) {
            $this->reservada = 0;
        }

        $this->pterecibir = round($this->pterecibir, self::MAX_DECIMALS);
        if ($this->pterecibir < 0) {
            $this->pterecibir = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->id()) ? parent::url($type, $list) : $this->getProduct()->url();
    }
}
