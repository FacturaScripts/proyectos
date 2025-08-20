<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;


/**
 * Description of ListTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 */
class ListTareaProyecto extends ListController
{

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'tasks';
        $data['icon'] = 'fa-solid fa-project-diagram';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsTasks();
        $this->createViewsPrivateTasks();
    }

    protected function createViewsPrivateTasks(string $viewName = 'ListTareaProyecto-private'): void
    {
        $this->addView($viewName, 'Join\TareaProyecto', 'private', 'fa-solid fa-unlock-alt')
            ->addOrderBy(['fecha'], 'date', 2)
            ->addOrderBy(['fechainicio'], 'start-date')
            ->addOrderBy(['fechafin'], 'end-date')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['descripcion'], 'description')
            ->addSearchFields(['tareas.nombre', 'tareas.descripcion']);

        $status = $this->codeModel->all('tareas_fases', 'idfase', 'nombre');

        // filtros
        $this->listView($viewName)
            ->addFilterPeriod('fecha', 'date', 'tareas.fecha')
            ->addFilterAutocomplete('idproyecto', 'project', 'tareas.idproyecto', 'proyectos', 'idproyecto', 'nombre')
            ->addFilterSelect('idfase', 'phase', 'tareas.idfase', $status);
    }

    protected function createViewsTasks(string $viewName = 'ListTareaProyecto')
    {
        $this->addView($viewName, 'Join\TareaProyecto', 'tasks', 'fa-solid fa-project-diagram')
            ->addOrderBy(['fecha'], 'date', 2)
            ->addOrderBy(['fechainicio'], 'start-date')
            ->addOrderBy(['fechafin'], 'end-date')
            ->addOrderBy(['nombre'], 'title')
            ->addOrderBy(['descripcion'], 'description')
            ->addSearchFields(['tareas.nombre', 'tareas.descripcion']);

        $status = $this->codeModel->all('tareas_fases', 'idfase', 'nombre');

        // filtros
        $this->listView($viewName)
            ->addFilterPeriod('fecha', 'date', 'tareas.fecha')
            ->addFilterAutocomplete('idproyecto', 'project', 'tareas.idproyecto', 'proyectos', 'idproyecto', 'nombre')
            ->addFilterSelect('idfase', 'phase', 'tareas.idfase', $status);
    }

    /**
     * @param string $viewName
     * @param ListView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListTareaProyecto':
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

            case 'ListTareaProyecto-private':
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
