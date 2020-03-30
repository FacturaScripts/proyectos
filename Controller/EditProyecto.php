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
namespace FacturaScripts\Plugins\Proyectos\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Lib\ExtendedController\EditView;

/**
 * Description of EditProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditProyecto extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Proyecto';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'project';
        $data['icon'] = 'fas fa-folder-open';
        $data['showonmenu'] = false;
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->createViewsBusinessDocument('PresupuestoProveedor', 'supplier-estimations');
        $this->createViewsBusinessDocument('PedidoProveedor', 'supplier-orders');
        $this->createViewsBusinessDocument('AlbaranProveedor', 'supplier-delivery-notes');
        $this->createViewsBusinessDocument('FacturaProveedor', 'supplier-invoices');
        $this->createViewsBusinessDocument('PresupuestoCliente', 'customer-estimations');
        $this->createViewsBusinessDocument('PedidoCliente', 'customer-orders');
        $this->createViewsBusinessDocument('AlbaranCliente', 'customer-delivery-notes');
        $this->createViewsBusinessDocument('FacturaCliente', 'customer-invoices');
    }

    /**
     * 
     * @param string $modelName
     * @param string $title
     */
    protected function createViewsBusinessDocument(string $modelName, string $title)
    {
        $viewName = 'List' . $modelName;
        $this->addListView($viewName, $modelName, $title, 'fas fa-copy');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['total'], 'total');
    }

    /**
     * 
     * @param string   $viewName
     * @param EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $idproyecto = $this->getViewModelValue($this->getMainViewName(), 'idproyecto');

        switch ($viewName) {
            case $this->getMainViewName():
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->model->codalmacen = $this->user->codalmacen;
                    $view->model->idempresa = $this->user->idempresa;
                    $view->model->nick = $this->user->nick;
                }
                break;

            default:
                $where = [new DataBaseWhere('idproyecto', $idproyecto)];
                $view->loadData('', $where);
                break;
        }
    }
}
