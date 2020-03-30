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
 * Description of ListProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListProyecto extends ListController
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
        $data['title'] = 'projects';
        $data['icon'] = 'fas fa-folder-open';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsProyects();
        $this->createViewsPrivateProyects();

        if ($this->user->admin) {
            $this->createViewsProyectStatus();
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsPrivateProyects(string $viewName = 'ListProyecto-private')
    {
        $this->addView($viewName, 'Proyecto', 'private', 'fas fa-unlock-alt');
        $this->addOrderBy($viewName, ['fecha'], 'date', 2);
        $this->addOrderBy($viewName, ['fechainicio'], 'start-date');
        $this->addOrderBy($viewName, ['fechafin'], 'end-date');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addSearchFields($viewName, ['nombre', 'descripcion']);

        /// filters
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');

        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');

        $status = $this->codeModel->all('proyectos_estados', 'idestado', 'nombre');
        $this->addFilterSelect($viewName, 'idestado', 'status', 'idestado', $status);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsProyects(string $viewName = 'ListProyecto')
    {
        $this->addView($viewName, 'Proyecto', 'projects', 'fas fa-folder-open');
        $this->addOrderBy($viewName, ['fecha'], 'date', 2);
        $this->addOrderBy($viewName, ['fechainicio'], 'start-date');
        $this->addOrderBy($viewName, ['fechafin'], 'end-date');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addSearchFields($viewName, ['nombre', 'descripcion']);

        /// filters
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->addFilterSelect($viewName, 'nick', 'admin', 'nick', $users);

        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');

        $status = $this->codeModel->all('proyectos_estados', 'idestado', 'nombre');
        $this->addFilterSelect($viewName, 'idestado', 'status', 'idestado', $status);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsProyectStatus(string $viewName = 'ListEstadoProyecto')
    {
        $this->addView($viewName, 'EstadoProyecto', 'states', 'fas fa-tags');
    }

    /**
     * 
     * @param string   $viewName
     * @param ListView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListProyecto':
                if ($this->user->admin) {
                    $view->loadData();
                    break;
                }
                $where = [
                    new DataBaseWhere('idempresa', $this->user->idempresa),
                    new DataBaseWhere('privado', false)
                ];
                $view->loadData('', $where);
                break;

            case 'ListProyecto-private':
                $sql = 'SELECT idproyecto FROM proyectos WHERE nick = ' . $this->dataBase->var2str($this->user->nick)
                    . ' UNION SELECT idproyecto FROM proyectos_users WHERE nick = ' . $this->dataBase->var2str($this->user->nick);
                $where = [
                    new DataBaseWhere('privado', true),
                    new DataBaseWhere('idproyecto', $sql, 'IN')
                ];
                $view->loadData('', $where);
                break;

            default:
                $view->loadData();
        }
    }
}
