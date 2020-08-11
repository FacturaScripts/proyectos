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
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

/**
 * Description of EditTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
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

    protected function createViews($viewName = 'EditTareaProyecto')
    {
        parent::createViews();
        $this->addEditView($viewName, 'TareaProyecto', 'task', 'fas fa-project-diagram');
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

                $project = new Proyecto();
                $where = [new DataBaseWhere('idproyecto', $view->model->idproyecto)];
                $project->loadFromCode('', $where);

                if (false === $project->userCanSee($this->user)) {
                    $this->setTemplate('Error/AccessDenied');
                }
                break;
        }
    }
}
