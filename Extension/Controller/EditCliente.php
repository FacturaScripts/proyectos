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
namespace FacturaScripts\Plugins\Proyectos\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of EditCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCliente
{

    public function createViews()
    {
        return function() {
            $viewName = 'ListProyecto';
            $this->addListView($viewName, 'Proyecto', 'projects', 'fas fa-folder-open');
            $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
            $this->views[$viewName]->addOrderBy(['fechainicio'], 'start-date');
            $this->views[$viewName]->addOrderBy(['fechafin'], 'end-date');
            $this->views[$viewName]->addOrderBy(['nombre'], 'name');
            $this->views[$viewName]->addOrderBy(['totalcompras'], 'total-purchases');
            $this->views[$viewName]->addOrderBy(['totalventas'], 'total-sales');
            $this->views[$viewName]->searchFields = ['nombre', 'descripcion'];

            /// disable customer column
            $this->views[$viewName]->disableColumn('customer');
        };
    }

    public function loadData()
    {
        return function($viewName, $view) {
            if ($viewName === 'ListProyecto') {
                $codcliente = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                $view->loadData('', $where);
            }
        };
    }
}
