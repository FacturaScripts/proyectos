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
namespace FacturaScripts\Plugins\Proyectos\Controller;

/**
 * Description of ListStockProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListStockProyecto extends \FacturaScripts\Core\Lib\ExtendedController\ListController
{

    /**
     * 
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'stock';
        $data['icon'] = 'fas fa-dolly';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsStocks();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsStocks(string $viewName = 'ListStockProyecto')
    {
        $this->addView($viewName, 'StockProyecto', 'stock', 'fas fa-dolly');
        $this->addSearchFields($viewName, ['referencia']);
        $this->addOrderBy($viewName, ['referencia'], 'reference');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity');
        $this->addOrderBy($viewName, ['disponible'], 'available');
        $this->addOrderBy($viewName, ['reservada'], 'reserved');
        $this->addOrderBy($viewName, ['pterecibir'], 'pending-reception');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);

        /// filters
        $this->addFilterAutocomplete($viewName, 'idproyecto', 'project', 'idproyecto', 'proyectos', 'idproyecto', 'nombre');
        $this->addFilterNumber($viewName, 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber($viewName, 'reservada', 'reserved', 'reservada');
        $this->addFilterNumber($viewName, 'pterecibir', 'pending-reception', 'pterecibir');
        $this->addFilterNumber($viewName, 'disponible', 'available', 'disponible');
    }
}
