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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditNotaProyecto
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditNotaProyecto extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'NotaProyecto';
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
        $data['title'] = 'note';
        $data['icon'] = 'fas fa-sticky-note';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Load view data.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case $this->getMainViewName():
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->model->nick = $this->user->nick;
                } elseif (false === $view->model->getProject()->userCanSee($this->user)) {
                    $this->setTemplate('Error/AccessDenied');
                }
                $this->views[$viewName]->disableColumn('task');
                break;
        }
    }
}
