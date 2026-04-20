<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Plugins\Proyectos\Model\EstadoProyecto;

/**
 * Description of ListProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListProyecto extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'projects';
        $data['icon'] = 'fa-brands fa-stack-overflow';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsProjects('ListProyecto', 'open-projects', 'fa-solid fa-lock-open');
        $this->createViewsProjects('ListProyecto-private', 'private', 'fa-solid fa-user-lock');
        $this->createViewsProjectsClosed('ListProyecto-closed', 'closed', 'fa-solid fa-lock');
    }

    protected function createViewsProjects(string $viewName, string $label, string $icon): void
    {
        $this->addView($viewName, 'Proyecto', $label, $icon)
            ->addOrderBy(['fecha', 'idproyecto'], 'date', 2)
            ->addOrderBy(['fechainicio'], 'start-date')
            ->addOrderBy(['fechafin'], 'end-date')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['totalcompras'], 'total-purchases')
            ->addOrderBy(['totalventas'], 'total-sales')
            ->addSearchFields(['nombre', 'descripcion']);

        // filtros
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $where = [
            ['label' => Tools::trans('only-active'), 'where' => [Where::eq('editable', true)]],
            ['label' => Tools::trans('only-closed'), 'where' => [Where::eq('editable', false)]],
            ['label' => Tools::trans('all'), 'where' => []]
        ];
        foreach ($this->codeModel->all('proyectos_estados', 'idestado', 'nombre') as $status) {
            $where[] = ['label' => ($status->description ?? $status->nombre), 'where' => [Where::eq('idestado', $status->code)]];
        }

        $this->listView($viewName)
            ->addFilterSelectWhere('status', $where)
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('nick', 'admin', 'nick', $users)
            ->addFilterNumber('totalcompras-gt', 'total-purchases', 'totalcompras', '>=')
            ->addFilterNumber('totalcompras-lt', 'total-purchases', 'totalcompras', '<=')
            ->addFilterNumber('totalventas-gt', 'total-sales', 'totalventas', '>=')
            ->addFilterNumber('totalventas-lt', 'total-sales', 'totalventas', '<=');

        $this->setProjectColors($viewName);
    }

    /**
     * @param string $viewName
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
                    Where::eq('idempresa', $this->user->idempresa),
                    Where::eq('privado', false)
                ];
                $view->loadData('', $where);
                break;

            case 'ListProyecto-private':
                $sql = 'SELECT idproyecto FROM proyectos WHERE nick = ' . $this->dataBase->var2str($this->user->nick)
                    . ' UNION SELECT idproyecto FROM proyectos_users WHERE nick = ' . $this->dataBase->var2str($this->user->nick);
                $where = [
                    Where::eq('privado', true),
                    Where::in('idproyecto', $sql)
                ];
                $view->loadData('', $where);
                break;

            case 'ListProyecto-closed':
                if ($this->user->admin) {
                    $view->loadData();
                    break;
                }
                $where = [
                    Where::eq('idempresa', $this->user->idempresa),
                    Where::eq('privado', false),
                    Where::eq('editable', false),
                ];
                $view->loadData('', $where);
                break;

            default:
                $view->loadData();
        }
    }

    protected function setProjectColors(string $viewName): void
    {
        // asignamos colores
        foreach (EstadoProyecto::all() as $estado) {
            if (empty($estado->color)) {
                continue;
            }

            $this->views[$viewName]->getRow('status')->options[] = [
                'tag' => 'option',
                'children' => [],
                'color' => $estado->color,
                'fieldname' => 'idestado',
                'text' => $estado->idestado,
                'title' => $estado->nombre
            ];
        }
    }

    protected function createViewsProjectsClosed(string $viewName, string $label, string $icon): void
    {
        $this->addView($viewName, 'Proyecto', $label, $icon)
            ->addOrderBy(['fecha', 'idproyecto'], 'date', 2)
            ->addOrderBy(['fechainicio'], 'start-date')
            ->addOrderBy(['fechafin'], 'end-date')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['totalcompras'], 'total-purchases')
            ->addOrderBy(['totalventas'], 'total-sales')
            ->addSearchFields(['nombre', 'descripcion']);
        // sólo estados no editables
        $where = [
            ['label' => Tools::trans('only-closed'), 'where' => [Where::eq('editable', false)]],
            ['label' => '------', 'where' => [Where::eq('editable', false)]],
        ];

        foreach (EstadoProyecto::all() as $estado) {
            if (false === $estado->editable) {
                $where[] = [
                    'label' => $estado->nombre,
                    'where' => [Where::eq('idestado', $estado->idestado)]
                ];
            }
        }

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->listView($viewName)
            ->addFilterSelectWhere('status', $where)
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('nick', 'admin', 'nick', $users)
            ->addFilterNumber('totalcompras-gt', 'total-purchases', 'totalcompras', '>=')
            ->addFilterNumber('totalcompras-lt', 'total-purchases', 'totalcompras', '<=')
            ->addFilterNumber('totalventas-gt', 'total-sales', 'totalventas', '>=')
            ->addFilterNumber('totalventas-lt', 'total-sales', 'totalventas', '<=')
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);

        $this->setProjectColors($viewName);
    }
}
