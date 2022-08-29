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
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Lib\ExtendedController\EditView;
use FacturaScripts\Dinamic\Lib\ProjectStockManager;
use FacturaScripts\Dinamic\Lib\ProjectTotalManager;
use FacturaScripts\Plugins\Servicios\Model\EstadoAT;

/**
 * Description of EditProyecto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditProyecto extends EditController
{

    public function getModelClassName(): string
    {
        return 'Proyecto';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'project';
        $data['icon'] = 'fab fa-stack-overflow';
        $data['showonmenu'] = false;
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createViewsTasks();
        $this->createViewsNotes();
        $this->createViewsStock();

        if (class_exists('\\FacturaScripts\\Dinamic\\Model\\ServicioAT')) {
            $this->createViewsServices();
        }

        $this->createViewsBusinessDocument('PresupuestoProveedor', 'supplier-estimations');
        $this->createViewsBusinessDocument('PedidoProveedor', 'supplier-orders');
        $this->createViewsBusinessDocument('AlbaranProveedor', 'supplier-delivery-notes');
        $this->createViewsBusinessDocument('FacturaProveedor', 'supplier-invoices');
        $this->createViewsBusinessDocument('PresupuestoCliente', 'customer-estimations');
        $this->createViewsBusinessDocument('PedidoCliente', 'customer-orders');
        $this->createViewsBusinessDocument('AlbaranCliente', 'customer-delivery-notes');
        $this->createViewsBusinessDocument('FacturaCliente', 'customer-invoices');
        $this->createViewsUsers();
    }

    protected function createViewsBusinessDocument(string $modelName, string $title)
    {
        $viewName = 'List' . $modelName;
        $this->addListView($viewName, $modelName, $title, 'fas fa-copy');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['total'], 'total');

        if (substr($viewName, -7) === 'Cliente') {
            $this->views[$viewName]->addSearchFields(['codigo', 'nombrecliente', 'numero2', 'observaciones']);
        } elseif (substr($viewName, -9) === 'Proveedor') {
            $this->views[$viewName]->addSearchFields(['codigo', 'nombre', 'numproveedor', 'observaciones']);
        }

        $this->addButton($viewName, [
            'type' => 'modal',
            'action' => 'link-up-' . $modelName,
            'icon' => 'fas fa-link',
            'label' => 'link-document'
        ]);
    }

    protected function createViewsNotes(string $viewName = 'ListNotaProyecto')
    {
        $this->addListView($viewName, 'NotaProyecto', 'notes', 'fas fa-sticky-note');
        $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['descripcion']);
    }

    protected function createViewsServices(string $viewName = 'ListServicioAT')
    {
        $this->addListView($viewName, 'ServicioAT', 'services', 'fas fa-headset');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['idprioridad'], 'priority');
        $this->views[$viewName]->addOrderBy(['idservicio'], 'code');
        $this->views[$viewName]->addOrderBy(['neto'], 'net');
        $this->views[$viewName]->addSearchFields(['descripcion', 'idservicio', 'material', 'observaciones', 'solucion']);

        // filters
        $this->views[$viewName]->addFilterPeriod('fecha', 'date', 'fecha');
        $this->views[$viewName]->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');
        $priority = $this->codeModel->all('serviciosat_prioridades', 'id', 'nombre');
        $this->views[$viewName]->addFilterSelect('idprioridad', 'priority', 'idprioridad', $priority);
        $status = $this->codeModel->all('serviciosat_estados', 'id', 'nombre');
        $this->views[$viewName]->addFilterSelect('idestado', 'status', 'idestado', $status);

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->views[$viewName]->addFilterSelect('nick', 'user', 'nick', $users);

        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $this->views[$viewName]->addFilterSelect('codagente', 'agent', 'codagente', $agents);

        $this->views[$viewName]->addFilterNumber('netogt', 'net', 'neto', '>=');
        $this->views[$viewName]->addFilterNumber('netolt', 'net', 'neto', '<=');

        // asignamos colores
        $estadoModel = new EstadoAT();
        foreach ($estadoModel->all() as $estado) {
            if (empty($estado->color)) {
                continue;
            }

            $this->views[$viewName]->getRow('status')->options[] = [
                'tag' => 'option',
                'children' => [],
                'color' => $estado->color,
                'fieldname' => 'idestado',
                'text' => $estado->id,
                'title' => $estado->nombre
            ];
        }
    }

    protected function createViewsStock(string $viewName = 'ListStockProyecto')
    {
        if (false === (bool)$this->toolBox()->appSettings()->get('proyectos', 'stock', false)) {
            return;
        }

        $this->addListView($viewName, 'StockProyecto', 'stock', 'fas fa-dolly');
        $this->views[$viewName]->addSearchFields(['referencia']);
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference');
        $this->views[$viewName]->addOrderBy(['cantidad'], 'quantity');
        $this->views[$viewName]->addOrderBy(['disponible'], 'available');
        $this->views[$viewName]->addOrderBy(['reservada'], 'reserved');
        $this->views[$viewName]->addOrderBy(['pterecibir'], 'pending-reception');

        // filters
        $this->views[$viewName]->addFilterNumber('cantidad', 'quantity', 'cantidad');
        $this->views[$viewName]->addFilterNumber('reservada', 'reserved', 'reservada');
        $this->views[$viewName]->addFilterNumber('pterecibir', 'pending-reception', 'pterecibir');
        $this->views[$viewName]->addFilterNumber('disponible', 'available', 'disponible');

        // disable column
        $this->views[$viewName]->disableColumn('project');

        // disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);

        if ($this->user->admin) {
            $this->addButton($viewName, [
                'action' => 'rebuild-stock',
                'color' => 'warning',
                'confirm' => true,
                'icon' => 'fas fa-magic',
                'label' => 'rebuild-stock'
            ]);
        }
    }

    protected function createViewsTasks(string $viewName = 'ListTareaProyecto')
    {
        $this->addListView($viewName, 'TareaProyecto', 'tasks', 'fas fa-project-diagram');
        $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['fechainicio'], 'start-date');
        $this->views[$viewName]->addOrderBy(['fechafin'], 'end-date');
        $this->views[$viewName]->addSearchFields(['descripcion', 'nombre']);

        // filters
        $this->views[$viewName]->addFilterPeriod('fecha', 'date', 'fecha');
        $status = $this->codeModel->all('tareas_fases', 'idfase', 'nombre');
        $this->views[$viewName]->addFilterSelect('idfase', 'phase', 'idfase', $status);

        // disable columns
        $this->views[$viewName]->disableColumn('project');
        $this->views[$viewName]->disableColumn('company');
    }

    protected function createViewsUsers(string $viewName = 'EditUserProyecto')
    {
        $this->addEditListView($viewName, 'UserProyecto', 'users', 'fas fa-users');

        // disable column
        $this->views[$viewName]->disableColumn('project');
    }

    /**
     * @param EditView $view
     */
    protected function disableProjectColumns(&$view)
    {
        foreach ($view->getColumns() as $group) {
            foreach ($group->columns as $col) {
                if ($col->name !== 'status') {
                    $view->disableColumn($col->name, false, 'true');
                }
            }
        }
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'rebuild-stock':
                $this->rebuildStockAction();
                return true;

            case 'link-up-PresupuestoCliente':
            case 'link-up-PedidoCliente':
            case 'link-up-AlbaranCliente':
            case 'link-up-FacturaCliente':
            case 'link-up-PresupuestoProveedor':
            case 'link-up-PedidoProveedor':
            case 'link-up-AlbaranProveedor':
            case 'link-up-FacturaProveedor':
                $parts = explode('-', $action);
                return $this->linkUpAction(end($parts));

            case 'unlink-up-PresupuestoCliente':
            case 'unlink-up-PedidoCliente':
            case 'unlink-up-AlbaranCliente':
            case 'unlink-up-FacturaCliente':
            case 'unlink-up-PresupuestoProveedor':
            case 'unlink-up-PedidoProveedor':
            case 'unlink-up-AlbaranProveedor':
            case 'unlink-up-FacturaProveedor':
                $parts = explode('-', $action);
                return $this->unlinkUpAction(end($parts));

            default:
                return parent::execPreviousAction($action);
        }
    }

    protected function linkUpAction(string $modelName): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-to-update');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $model = new $modelClass();
        $modelTable = $model->tableName();
        $modelKey = $model->primaryColumn();
        $code = $this->request->request->get('linkupcode', '');
        $idproyecto = $this->request->get('code', '');

        $sql = "UPDATE $modelTable SET idproyecto = " . $this->dataBase->var2str($idproyecto)
            . " WHERE " . $this->dataBase->escapeColumn($modelKey) . " = " . $this->dataBase->var2str($code) . ';';
        if (false === $this->dataBase->exec($sql)) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return true;
        }

        $this->toolBox()->i18nLog()->info('record-updated-correctly');
        return true;
    }

    /**
     * @param string $viewName
     * @param EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $idproyecto = $this->getViewModelValue($mainViewName, 'idproyecto');

        switch ($viewName) {
            case $mainViewName:
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->model->idempresa = $this->user->idempresa;
                    $view->model->nick = $this->user->nick;
                } elseif (false === $view->model->userCanSee($this->user)) {
                    $this->setTemplate('Error/AccessDenied');
                } elseif (false === $view->model->editable) {
                    $this->disableProjectColumns($view);
                    $this->setSettings('EditUserProyecto', 'active', false);
                } elseif (false === $view->model->privado) {
                    $this->setSettings('EditUserProyecto', 'active', false);
                }
                break;

            case 'EditUserProyecto':
            case 'ListNotaProyecto':
            case 'ListStockProyecto':
            case 'ListTareaProyecto':
            case 'ListServicioAT':
                $where = [new DataBaseWhere('idproyecto', $idproyecto)];
                $view->loadData('', $where);
                break;

            case 'ListAlbaranCliente':
            case 'ListAlbaranProveedor':
            case 'ListFacturaCliente':
            case 'ListFacturaProveedor':
            case 'ListPedidoCliente':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoCliente':
            case 'ListPresupuestoProveedor':
                $where = [new DataBaseWhere('idproyecto', $idproyecto)];
                $view->loadData('', $where);
                if ($view->count > 0) {
                    $this->addButton($viewName, [
                        'type' => 'action',
                        'action' => 'unlink-up-' . $view->model->modelClassName(),
                        'color' => 'warning',
                        'icon' => 'fas fa-unlink',
                        'label' => 'unlink-document'
                    ]);
                }
                break;
        }
    }

    protected function rebuildStockAction(int $idproyecto = null)
    {
        if (empty($idproyecto)) {
            $idproyecto = (int)$this->request->query->get('code');
        }

        if (ProjectStockManager::rebuild($idproyecto)) {
            ProjectTotalManager::recalculate($idproyecto);

            // limpiamos caché para forzar actualizar los totales de los listados
            self::toolBox()::cache()->clear();
            self::toolBox()::i18nLog()->notice('project-stock-rebuild-ok');
            return;
        }

        self::toolBox()::i18nLog()->warning('project-stock-rebuild-error');
    }

    protected function unlinkUpAction(string $modelName): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-to-update');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $model = new $modelClass();
        $modelTable = $model->tableName();
        $modelKey = $model->primaryColumn();
        $codes = $this->request->request->get('code', []);
        if (empty($codes)) {
            self::toolBox()->i18nLog()->warning('no-selected-item');
            return true;
        }

        foreach ($codes as $code) {
            $sql = "UPDATE $modelTable SET idproyecto = NULL WHERE "
                . $this->dataBase->escapeColumn($modelKey) . " = " . $this->dataBase->var2str($code) . ';';
            if (false === $this->dataBase->exec($sql)) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                return false;
            }
        }

        $this->toolBox()->i18nLog()->info('record-updated-correctly');
        return true;
    }
}
