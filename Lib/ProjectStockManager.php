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
namespace FacturaScripts\Plugins\Proyectos\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Plugins\Proyectos\Model\StockProyecto;

/**
 * Description of ProjectStockManager
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProjectStockManager
{

    /**
     * 
     * @param BusinessDocumentLine $line
     * @param array                $linePrevData
     * @param int                  $fromIdproyecto
     * @param int                  $toIdproyecto
     */
    public static function lineTransfer($line, $linePrevData, $fromIdproyecto, $toIdproyecto)
    {
        /// find the project stock
        $fromStock = new StockProyecto();
        $where = [
            new DataBaseWhere('idproyecto', $fromIdproyecto),
            new DataBaseWhere('referencia', $line->referencia)
        ];
        if (!empty($fromIdproyecto) && $fromStock->loadFromCode('', $where)) {
            static::applyProyectStockChanges($linePrevData['actualizastock'], $linePrevData['cantidad'] * -1, $fromStock);
            $fromStock->save();
        }

        /// find new project stock
        $toStock = new StockProyecto();
        $where2 = [
            new DataBaseWhere('idproyecto', $toIdproyecto),
            new DataBaseWhere('referencia', $line->referencia)
        ];
        if (empty($toIdproyecto)) {
            return;
        } elseif (false === $toStock->loadFromCode('', $where2)) {
            /// stock not found, then create one
            $toStock->idproducto = $line->idproducto;
            $toStock->idproyecto = $toIdproyecto;
            $toStock->referencia = $line->referencia;
        }

        static::applyProyectStockChanges($line->actualizastock, $line->cantidad, $toStock);
        $toStock->save();
    }

    /**
     * 
     * @param BusinessDocumentLine $line
     * @param array                $linePrevData
     */
    public static function updateLineStock($line, $linePrevData)
    {
        $idproyecto = $line->getDocument()->idproyecto;
        if (empty($idproyecto)) {
            return;
        }

        /// find the project stock
        $stock = new StockProyecto();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('referencia', $line->referencia)
        ];
        if (false === $stock->loadFromCode('', $where)) {
            /// stock not found, then create one
            $stock->idproducto = $line->idproducto;
            $stock->idproyecto = $idproyecto;
            $stock->referencia = $line->referencia;
        }

        static::applyProyectStockChanges($linePrevData['actualizastock'], $linePrevData['cantidad'] * -1, $stock);
        static::applyProyectStockChanges($line->actualizastock, $line->cantidad, $stock);
        return $stock->save();
    }

    /**
     * 
     * @param int           $mode
     * @param float         $quantity
     * @param StockProyecto $stock
     */
    protected static function applyProyectStockChanges($mode, $quantity, $stock)
    {
        switch ($mode) {
            case 1:
            case -1:
                $stock->cantidad += $mode * $quantity;
                break;

            case 2:
                $stock->pterecibir += $quantity;
                break;

            case -2:
                $stock->reservada += $quantity;
                break;
        }
    }
}
