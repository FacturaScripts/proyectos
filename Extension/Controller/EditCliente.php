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
            $this->addListView('ListProyecto', 'Proyecto', 'projects', 'fas fa-folder-open');
            $this->views['ListProyecto']->addOrderBy(['fecha'], 'date', 2);
            $this->views['ListProyecto']->addOrderBy(['fechainicio'], 'start-date');
            $this->views['ListProyecto']->addOrderBy(['fechafin'], 'end-date');
            $this->views['ListProyecto']->addOrderBy(['nombre'], 'name');
            $this->views['ListProyecto']->disableColumn('customer');
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
