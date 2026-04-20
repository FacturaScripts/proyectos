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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;


/**
 * Description of ListTarea
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
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
        $this->createViewsMine();
        $this->createViewsPrivateTasks();
    }

    protected function createViewsMine(string $viewName = 'ListTareaProyecto-mine')
    {
        $this->addView($viewName, 'Join\\TareaProyecto', 'Mis tareas', 'fa-solid fa-list-check')
            ->addOrderBy(['fecha'], 'date', 2)
            ->addOrderBy(['fechainicio'], 'start-date')
            ->addOrderBy(['fechafin'], 'end-date')
            ->addOrderBy(['nombre'], 'title')
            ->addOrderBy(['descripcion'], 'description')
            ->addSearchFields(['tareas.nombre', 'tareas.descripcion']);

        $status = $this->codeModel->all('tareas_fases', 'idfase', 'nombre');

        // filtros
        $this->listView($viewName)
            ->addFilterPeriod('fecha', 'start-date', 'tareas.fecha')
            ->addFilterPeriod('fechafin', 'end-date', 'tareas.fechafin')
            ->addFilterAutocomplete('idproyecto', 'project', 'tareas.idproyecto', 'proyectos', 'idproyecto', 'nombre')
            ->addFilterSelect('idfase', 'phase', 'tareas.idfase', $status);
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
            ->addFilterPeriod('fecha', 'start-date', 'tareas.fecha')
            ->addFilterPeriod('fechafin', 'end-date', 'tareas.fechafin')
            ->addFilterAutocomplete('idproyecto', 'project', 'tareas.idproyecto', 'proyectos', 'idproyecto', 'nombre')
            ->addFilterSelect('idfase', 'phase', 'tareas.idfase', $status);

        // filtro por usuario asignado
        $users = $this->codeModel->all('users', 'nick', 'nick');
        if (count($users) > 1) {
            $this->listView($viewName)->addFilterSelect('nick', 'user', 'tareas.nick', $users);
        }
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
            ->addFilterPeriod('fecha', 'start-date', 'tareas.fecha')
            ->addFilterPeriod('fechafin', 'end-date', 'tareas.fechafin')
            ->addFilterAutocomplete('idproyecto', 'project', 'tareas.idproyecto', 'proyectos', 'idproyecto', 'nombre')
            ->addFilterSelect('idfase', 'phase', 'tareas.idfase', $status);

        // filtro por usuario asignado
        $users = $this->codeModel->all('users', 'nick', 'nick');
        if (count($users) > 1) {
            $this->listView($viewName)->addFilterSelect('nick', 'user', 'tareas.nick', $users);
        }
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
                    Where::eq('proyectos.idempresa', $this->user->idempresa),
                    Where::eq('proyectos.privado', false)
                ];
                $view->loadData('', $where);
                break;

            case 'ListTareaProyecto-mine':
                // projects accessible to the user (public in same company OR owned by user OR user assigned)
                $sql = 'SELECT idproyecto FROM proyectos WHERE idempresa = ' . $this->dataBase->var2str($this->user->idempresa)
                    . ' UNION SELECT idproyecto FROM proyectos WHERE nick = ' . $this->dataBase->var2str($this->user->nick)
                    . ' UNION SELECT idproyecto FROM proyectos_users WHERE nick = ' . $this->dataBase->var2str($this->user->nick);
                $where = [
                    Where::eq('tareas.nick', $this->user->nick),
                    Where::in('tareas.idproyecto', $sql)
                ];
                $view->loadData('', $where);
                break;

            case 'ListTareaProyecto-private':
                $sql = 'SELECT idproyecto FROM proyectos WHERE nick = ' . $this->dataBase->var2str($this->user->nick)
                    . ' UNION SELECT idproyecto FROM proyectos_users WHERE nick = ' . $this->dataBase->var2str($this->user->nick);
                $where = [
                    Where::eq('proyectos.privado', true),
                    Where::in('tareas.idproyecto', $sql)
                ];
                $view->loadData('', $where);
                break;

            default:
                $view->loadData();
        }
    }
}
