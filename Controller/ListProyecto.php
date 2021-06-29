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
        $data['icon'] = 'fab fa-stack-overflow';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsProyects();
        $this->createViewsPrivateProyects();
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsPrivateProyects(string $viewName = 'ListProyecto-private')
    {
        $this->addView($viewName, 'Proyecto', 'private', 'fas fa-unlock-alt');
        $this->addOrderBy($viewName, ['fecha', 'idproyecto'], 'date', 2);
        $this->addOrderBy($viewName, ['fechainicio'], 'start-date');
        $this->addOrderBy($viewName, ['fechafin'], 'end-date');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['totalcompras'], 'total-purchases');
        $this->addOrderBy($viewName, ['totalventas'], 'total-sales');
        $this->addSearchFields($viewName, ['nombre', 'descripcion']);

        /// filters
        $where = [
            ['label' => $this->toolBox()->i18n()->trans('only-active'), 'where' => [new DataBaseWhere('editable', true)]],
            ['label' => $this->toolBox()->i18n()->trans('only-closed'), 'where' => [new DataBaseWhere('editable', false)]],
            ['label' => $this->toolBox()->i18n()->trans('all'), 'where' => []]
        ];
        foreach ($this->codeModel->all('proyectos_estados', 'idestado', 'nombre') as $status) {
            $where[] = ['label' => $status->description, 'where' => [new DataBaseWhere('idestado', $status->code)]];
        }
        $this->addFilterSelectWhere($viewName, 'status', $where);

        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');
        $this->addFilterNumber($viewName, 'totalcompras-gt', 'total-purchases', 'totalcompras', '>=');
        $this->addFilterNumber($viewName, 'totalcompras-lt', 'total-purchases', 'totalcompras', '<=');
        $this->addFilterNumber($viewName, 'totalventas-gt', 'total-sales', 'totalventas', '>=');
        $this->addFilterNumber($viewName, 'totalventas-lt', 'total-sales', 'totalventas', '<=');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsProyects(string $viewName = 'ListProyecto')
    {
        $this->addView($viewName, 'Proyecto', 'projects', 'fab fa-stack-overflow');
        $this->addOrderBy($viewName, ['fecha', 'idproyecto'], 'date', 2);
        $this->addOrderBy($viewName, ['fechainicio'], 'start-date');
        $this->addOrderBy($viewName, ['fechafin'], 'end-date');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['totalcompras'], 'total-purchases');
        $this->addOrderBy($viewName, ['totalventas'], 'total-sales');
        $this->addSearchFields($viewName, ['nombre', 'descripcion']);

        /// filters
        $where = [
            ['label' => $this->toolBox()->i18n()->trans('only-active'), 'where' => [new DataBaseWhere('editable', true)]],
            ['label' => $this->toolBox()->i18n()->trans('only-closed'), 'where' => [new DataBaseWhere('editable', false)]],
            ['label' => $this->toolBox()->i18n()->trans('all'), 'where' => []]
        ];
        foreach ($this->codeModel->all('proyectos_estados', 'idestado', 'nombre') as $status) {
            $where[] = ['label' => $status->description, 'where' => [new DataBaseWhere('idestado', $status->code)]];
        }
        $this->addFilterSelectWhere($viewName, 'status', $where);

        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->addFilterSelect($viewName, 'nick', 'admin', 'nick', $users);

        $this->addFilterNumber($viewName, 'totalcompras-gt', 'total-purchases', 'totalcompras', '>=');
        $this->addFilterNumber($viewName, 'totalcompras-lt', 'total-purchases', 'totalcompras', '<=');
        $this->addFilterNumber($viewName, 'totalventas-gt', 'total-sales', 'totalventas', '>=');
        $this->addFilterNumber($viewName, 'totalventas-lt', 'total-sales', 'totalventas', '<=');
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
