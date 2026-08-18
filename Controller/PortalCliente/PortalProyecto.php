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

namespace FacturaScripts\Plugins\Proyectos\Controller\PortalCliente;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\PortalDocShare;
use FacturaScripts\Dinamic\Lib\PortalViewController;

/**
 * Ficha de un proyecto en el portal del cliente.
 *
 * Muestra el proyecto con sus pestañas: datos del proyecto, tareas y archivos
 * adjuntos. Se accede por la url amigable PortalProyecto/{pc_uuid} y solo puede verlo el
 * contacto del cliente del proyecto, con permiso para ver proyectos.
 *
 * Depende de las clases del plugin PortalCliente; solo se registra en el menú y en las
 * rutas cuando ese plugin está activo (ver Init.php).
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class PortalProyecto extends PortalViewController
{
    public function getModelClassName(): string
    {
        return 'Proyecto';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'PortalCliente';
        $data['title'] = 'project';
        $data['icon'] = 'fa-solid fa-diagram-project';
        return $data;
    }

    public function portalMenuActive(): string
    {
        return 'ListPortalProyecto';
    }

    /**
     * Crea la pestaña de archivos adjuntos del proyecto.
     *
     * @param string $viewName Nombre de la vista a crear.
     *
     * @return void
     */
    protected function createViewDocFiles(string $viewName = 'docfiles'): void
    {
        $this->addHtmlView($viewName, 'Tab/PortalDocFiles', 'AttachedFileRelation', 'files', 'fa-solid fa-paperclip');
    }

    protected function createViews()
    {
        $model = $this->preloadModel();
        if (false === $model->exists()) {
            $this->error404();
            return;
        }

        $this->setContactPermissions($model);
        if (false === $this->permissions->allowAccess) {
            $this->error403();
            return;
        }

        parent::createViews();

        $this->addHtmlView('info', 'Tab/PortalInfoProyecto', 'Proyecto', 'project', 'fa-solid fa-info-circle');
        $this->addHtmlView('tasks', 'Tab/PortalTareasProyecto', 'Proyecto', 'tasks', 'fa-solid fa-list-check');
        $this->createViewDocFiles();
    }

    /**
     * Atiende las acciones del proyecto: imprimir.
     *
     * @param string $action Acción solicitada.
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        return match ($action) {
            'print' => $this->printAction(),
            default => parent::execPreviousAction($action),
        };
    }

    /**
     * Indica que el proyecto se localiza por su identificador público (pc_uuid) en las
     * urls amigables del portal.
     *
     * @return string
     */
    protected function getComposeUrlColumn(): string
    {
        return 'pc_uuid';
    }

    /**
     * Carga los datos de cada pestaña y compone el título de la página con el nombre del
     * proyecto. La pestaña de archivos solo se activa si hay archivos visibles.
     *
     * @param string $viewName Nombre de la vista a cargar.
     * @param mixed $view Vista a rellenar.
     *
     * @return void
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'docfiles':
                $mainModel = $this->views[self::MAIN_VIEW_NAME]->model;
                $view->cursor = $mainModel->getPortalFiles();
                $view->count = count($view->cursor);
                $view->setSettings('active', $view->count > 0);
                break;

            case 'tasks':
                $mainModel = $this->views[self::MAIN_VIEW_NAME]->model;
                $view->count = count($mainModel->getTasks());
                break;

            case self::MAIN_VIEW_NAME:
                parent::loadData($viewName, $view);
                $this->title = Tools::lang()->trans('project') . ' ' . $view->model->nombre;
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Genera y descarga el PDF del proyecto.
     *
     * @return bool Siempre false, porque la respuesta es el propio archivo.
     */
    private function printAction(): bool
    {
        if (false === $this->permissions->allowAccess) {
            Tools::log()->warning('access-denied');
            return true;
        }

        $this->setTemplate(false);
        $exportManager = new ExportManager();
        $exportManager->newDoc($exportManager->defaultOption());
        $exportManager->addModelPage($this->preloadModel(), [], Tools::lang()->trans('project'));
        $exportManager->show($this->response);
        return false;
    }

    /**
     * Asigna los permisos del visitante sobre el proyecto.
     *
     * Solo da acceso al contacto identificado que tenga permiso para ver proyectos y
     * pertenezca al cliente del proyecto. En cualquier otro caso deniega el acceso.
     *
     * @param mixed $model Proyecto que se está consultando.
     *
     * @return void
     */
    private function setContactPermissions($model): void
    {
        // enlace de compartición
        $codeShare = $this->request->get('share');
        if ($codeShare && PortalDocShare::checkCode($model, $codeShare)) {
            $this->permissions->set(true, 1, false, false);
            return;
        }

        if (false === $this->contact->exists()) {
            $this->permissions->set(false, 0, false, false);
            return;
        }

        if (false === (bool)$this->contact->pc_allow_show_project) {
            $this->permissions->set(false, 0, false, false);
            return;
        }

        if (false === empty($model->codcliente) && $model->codcliente === $this->contact->codcliente) {
            $this->permissions->set(true, 1, false, false);
            return;
        }

        $this->permissions->set(false, 0, false, false);
    }
}
