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
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Lib\ExtendedController\ListView;

/**
 * Description of ListTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class ListTarea extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'tasks';
        $data['icon'] = 'fas fa-project-diagram';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsTasks();
        $this->createViewsPrivateTasks();

        if ($this->user->admin) {
            $this->createViewsTaskStatus();
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsPrivateTasks(string $viewName = 'ListTarea-private')
    {
        $this->addView($viewName, 'ModelView\Tarea', 'private', 'fas fa-unlock-alt');
        $this->addOrderBy($viewName, ['fecha'], 'date', 2);
        $this->addOrderBy($viewName, ['fechainicio'], 'start-date');
        $this->addOrderBy($viewName, ['fechafin'], 'end-date');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addSearchFields($viewName, ['nombre', 'descripcion']);

        /// filters
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');

        $this->addFilterAutocomplete($viewName, 'idproyecto', 'project', 'idproyecto', 'proyectos', 'idproyecto', 'nombre');

        $status = $this->codeModel->all('tareas_fases', 'idfase', 'nombre');
        $this->addFilterSelect($viewName, 'idfase', 'phase', 'idfase', $status);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsTasks(string $viewName = 'ListTarea')
    {
        $this->addView($viewName, 'ModelView\Tarea', 'tasks', 'fas fa-project-diagram');
        $this->addOrderBy($viewName, ['fecha'], 'date', 2);
        $this->addOrderBy($viewName, ['fechainicio'], 'start-date');
        $this->addOrderBy($viewName, ['fechafin'], 'end-date');
        $this->addOrderBy($viewName, ['nombre'], 'title');
        $this->addSearchFields($viewName, ['nombre', 'descripcion']);

        /// filters
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');

        $this->addFilterAutocomplete($viewName, 'idproyecto', 'project', 'idproyecto', 'proyectos', 'idproyecto', 'nombre');

        $status = $this->codeModel->all('tareas_fases', 'idfase', 'nombre');
        $this->addFilterSelect($viewName, 'idfase', 'phase', 'idfase', $status);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsTaskStatus(string $viewName = 'ListFaseTarea')
    {
        $this->addView($viewName, 'FaseTarea', 'phases', 'fas fa-tags');
    }

    /**
     * 
     * @param string   $viewName
     * @param ListView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListTarea':
                if ($this->user->admin) {
                    $view->loadData();
                    break;
                }
                $where = [
                    new DataBaseWhere('proyectos.idempresa', $this->user->idempresa),
                    new DataBaseWhere('proyectos.privado', false)
                ];
                $view->loadData('', $where);
                break;

            case 'ListTarea-private':
                $sql = 'SELECT idproyecto FROM proyectos WHERE nick = ' . $this->dataBase->var2str($this->user->nick)
                    . ' UNION SELECT idproyecto FROM proyectos_users WHERE nick = ' . $this->dataBase->var2str($this->user->nick);
                $where = [
                    new DataBaseWhere('proyectos.privado', true),
                    new DataBaseWhere('tareas.idproyecto', $sql, 'IN')
                ];
                $view->loadData('', $where);
                break;

            default:
                $view->loadData();
        }
    }
}
