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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Description of BusinessDocument
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Stock
{

    public function saveUpdateBefore()
    {
        return function() {
            if (!empty($this->referencia)) {
                $this->setAvailableStock();
            }
            return true;
        };
    }

    public function saveInsertBefore()
    {
        return function() {
            if (!empty($this->referencia)) {
                $this->setAvailableStock();
            }
            return true;
        };
    }

    protected function setAvailableStock()
    {
        return function() {
            $burnStock = AppSettings::get('projects', 'burnstock', 0);
            if ($burnStock == 1) {
                $where = [
                    new DataBaseWhere('referencia', $this->referencia),
                    new DataBaseWhere('disponible', 0, '>'),
                ];
                $total = TotalModel::sum('proyectos_stocks', 'disponible' , $where);
                $this->reservado += $total;
                $this->disponible -= $total;
            }
        };
    }
}
