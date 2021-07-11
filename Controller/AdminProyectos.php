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
namespace FacturaScripts\Plugins\Proyectos\Controller;

use FacturaScripts\Dinamic\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\ExtendedController\PanelController;

/**
 * Description of AdminProyectos
 *
 * @author Carlos Garcia Gomez              <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 */
class AdminProyectos extends PanelController
{

    private const VIEW_CONFIG_PROJECTS = 'ConfigProyectos';
    private const VIEW_LIST_STATUS = 'EditEstadoProyecto';
    private const VIEW_LIST_PHASES = 'EditFaseTarea';

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'projects';
        $data['icon'] = 'fab fa-stack-overflow';
        return $data;
    }

    /**
     * Inserts the views or tabs to display.
     */
    protected function createViews()
    {
        $this->setTemplate('EditSettings');
        $this->createViewEditConfig();
        $this->createViewStatus();
        $this->createViewPhases();
    }

    /**
     *
     * @param string $viewName
     */
    private function createViewEditConfig(string $viewName = self::VIEW_CONFIG_PROJECTS)
    {
        $this->addEditView($viewName, 'Settings', 'general');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     *
     * @param string $viewName
     */
    private function createViewPhases(string $viewName = self::VIEW_LIST_PHASES)
    {
        $this->addEditListView($viewName, 'FaseTarea', 'phases', 'fas fa-hourglass-half');
        $this->views[$viewName]->setInLine(true);
    }

    /**
     *
     * @param string $viewName
     */
    private function createViewStatus(string $viewName = self::VIEW_LIST_STATUS)
    {
        $this->addEditListView($viewName, 'EstadoProyecto', 'states', 'fas fa-tags');
        $this->views[$viewName]->setInLine(true);
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEW_CONFIG_PROJECTS:
                $view->loadData('proyectos');
                $view->model->name = 'proyectos';
                break;

            case self::VIEW_LIST_PHASES:
            case self::VIEW_LIST_STATUS:
                $view->loadData();
                break;
        }
    }
}
