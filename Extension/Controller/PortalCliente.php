<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2026 Daniel Fernández Giménez <contacto@danielfg.es>
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

namespace FacturaScripts\Plugins\Proyectos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Añade la pestaña de proyectos al panel del portal cliente (PortalCliente), sin modificar
 * ese plugin: se apoya en los pipe('createViews') y pipe('loadData') que ya dispara
 * PortalPanelController::commonCore(), igual que las extensiones de los controladores del core.
 *
 * Cada fila del listado enlaza con la ficha propia del proyecto (Controller\PortalProyecto),
 * a través de Proyecto::url('public').
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class PortalCliente
{
    public function createViews(): Closure
    {
        return function () {
            $statuses = CodeModel::all('proyectos_estados', 'idestado', 'nombre');

            $this->addListView('ListPortalProyecto', 'Proyecto', 'projects', 'fa-solid fa-diagram-project')
                ->addOrderBy(['fecha'], 'date', 2)
                ->addOrderBy(['nombre'], 'name')
                ->addSearchFields(['nombre', 'descripcion'])
                ->addFilterSelect('status', 'status', 'idestado', $statuses);

            $this->setSettings('ListPortalProyecto', 'btnNew', false);
            $this->setSettings('ListPortalProyecto', 'btnDelete', false);
            $this->setSettings('ListPortalProyecto', 'checkBoxes', false);
        };
    }

    public function loadData(): Closure
    {
        return function (string $viewName, $view) {
            if ($viewName !== 'ListPortalProyecto') {
                return;
            }

            // sin cliente asociado, o sin permiso, no hay nada que mostrar
            if (empty($this->contact->codcliente) || false === (bool)$this->contact->pc_allow_show_project) {
                $view->count = 0;
                $this->setSettings($viewName, 'active', false);
                return;
            }

            $where = [Where::eq('codcliente', $this->contact->codcliente)];
            $view->loadData('', $where);
            $this->setSettings($viewName, 'active', $view->count > 0 || $view->showFilters);
        };
    }
}
