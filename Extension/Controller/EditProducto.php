<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of EditProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditProducto
{

    public function createViews()
    {
        return function() {
            $viewName = 'ListStockProyecto';
            $this->addListView($viewName, 'StockProyecto', 'projects', 'fas fa-folder-open');
            $this->views[$viewName]->addSearchFields(['referencia']);
            $this->views[$viewName]->addOrderBy(['referencia'], 'reference');
            $this->views[$viewName]->addOrderBy(['cantidad'], 'quantity');
            $this->views[$viewName]->addOrderBy(['disponible'], 'available');
            $this->views[$viewName]->addOrderBy(['reservada'], 'reserved');
            $this->views[$viewName]->addOrderBy(['pterecibir'], 'pending-reception');

            /// disable description column
            $this->views[$viewName]->disableColumn('description');
            
            /// disable buttons
            $this->setSettings($viewName, 'btnDelete', false);
            $this->setSettings($viewName, 'btnNew', false);
            $this->setSettings($viewName, 'checkBoxes', false);
        };
    }

    public function loadData()
    {
        return function($viewName, $view) {
            if ($viewName === 'ListStockProyecto') {
                $idproducto = $this->getViewModelValue($this->getMainViewName(), 'idproducto');
                $where = [new DataBaseWhere('idproducto', $idproducto)];
                $view->loadData('', $where);
            }
        };
    }
}
