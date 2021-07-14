<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\Proyectos\Extension\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Proyectos\Model\StockProyecto;

/**
 * Description of Stock
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Stock
{

    public function saveUpdateBefore()
    {
        return function() {
            return $this->setAvailableStock();
        };
    }

    public function saveInsertBefore()
    {
        return function() {
            return $this->setAvailableStock();
        };
    }

    protected function setAvailableStock()
    {
        return function() {
            $burnStock = (bool) $this->toolBox()->appSettings()->get('proyectos', 'burnstock', 0);
            if (false === $burnStock) {
                return true;
            }

            $stockProjectModel = new StockProyecto();
            $where = [
                new DataBaseWhere('referencia', $this->referencia),
                new DataBaseWhere('disponible', 0, '>')
            ];
            foreach ($stockProjectModel->all($where) as $stock) {
                $this->disponible -= $stock->disponible;
            }

            return true;
        };
    }
}
