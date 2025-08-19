<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Proyectos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;


/**
 * Description of EditCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCliente
{
    public function createViews(): Closure
    {
        return function () {
            $this->addListView('ListProyecto', 'Proyecto', 'projects', 'fa-brands fa-stack-overflow')
                ->addOrderBy(['fecha'], 'date', 2)
                ->addOrderBy(['fechainicio'], 'start-date')
                ->addOrderBy(['fechafin'], 'end-date')
                ->addOrderBy(['nombre'], 'name')
                ->addOrderBy(['totalcompras'], 'total-purchases')
                ->addOrderBy(['totalventas'], 'total-sales')
                ->addSearchFields(['nombre', 'descripcion'])
                ->disableColumn('customer');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'ListProyecto') {
                $codcliente = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                $view->loadData('', $where);
            }
        };
    }
}
