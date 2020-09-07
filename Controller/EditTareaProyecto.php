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
 * Description of EditTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 */
class EditTareaProyecto extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'TareaProyecto';
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
        $data['title'] = 'task';
        $data['icon'] = 'fas fa-project-diagram';
        $data['showonmenu'] = false;
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');
        $this->createViewsNotes();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsNotes(string $viewName = 'EditNotaProyecto')
    {
        $this->addEditListView($viewName, 'NotaProyecto', 'notes', 'fas fa-sticky-note');

        /// hide project and task columns
        $this->views[$viewName]->disableColumn('project');
        $this->views[$viewName]->disableColumn('task');
    }

    /**
     * 
     * @param EditView $view
     */
    protected function disableTaskColumns(&$view)
    {
        foreach ($view->getColumns() as $group) {
            foreach ($group->columns as $col) {
                if ($col->name !== 'phase') {
                    $view->disableColumn($col->name, false, 'true');
                }
            }
        }
    }

    /**
     * 
     * @param string   $viewName
     * @param EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        switch ($viewName) {
            case $mainViewName:
                parent::loadData($viewName, $view);
                if (false === $view->model->getProject()->userCanSee($this->user)) {
                    $this->setTemplate('Error/AccessDenied');
                } elseif (false === $view->model->getProject()->editable) {
                    $this->disableTaskColumns($view);
                    $this->views['EditTareaProyecto']->disableColumn('code');
                }
                break;

            case 'EditNotaProyecto':
                $where = [new DataBaseWhere('idtarea', $this->getViewModelValue($mainViewName, 'idtarea'))];
                $view->loadData('', $where, ['fecha' => 'DESC']);
                if (false === $view->model->exists()) {
                    $view->model->idproyecto = $this->getViewModelValue($mainViewName, 'idproyecto');
                    $view->model->nick = $this->user->nick;
                }
                break;
        }
    }
}
