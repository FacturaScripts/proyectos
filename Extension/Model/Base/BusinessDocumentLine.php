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
namespace FacturaScripts\Plugins\Proyectos\Extension\Model\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Proyectos\Model\StockProyecto;

/**
 * Description of BusinessDocumentLine
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class BusinessDocumentLine
{

    protected function applyProyectStockChanges()
    {
        return function($mode, $quantity, $stock) {
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
        };
    }

    protected function updateStock()
    {
        return function() {
            $idproyecto = $this->getDocument()->idproyecto;

            /// find the project stock
            $stock = new StockProyecto();
            $where = [
                new DataBaseWhere('idproyecto', $idproyecto),
                new DataBaseWhere('referencia', $this->referencia)
            ];
            if (false === $stock->loadFromCode('', $where)) {
                /// stock not found, then create one
                $stock->idproducto = $this->idproducto;
                $stock->idproyecto = $idproyecto;
                $stock->referencia = $this->referencia;
            }

            $this->applyProyectStockChanges($this->previousData['actualizastock'], $this->previousData['cantidad'] * -1, $stock);
            $this->applyProyectStockChanges($this->actualizastock, $this->cantidad, $stock);
            return $stock->save();
        };
    }
}
