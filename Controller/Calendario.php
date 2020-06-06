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

use FacturaScripts\Core\Base;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Plugins\Proyectos\Model\Tarea;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of EditTarea
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class Calendario extends Base\Controller
{
    public $CalendarLang = 'en-gb';
    
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'calendar';
        $data['icon'] = 'fas fa-calendar-alt';
        return $data;
    }
    
    /**
     * Runs the controller's private logic.
     *
     * @param Response                      $response
     * @param User                          $user
     * @param Base\ControllerPermissions    $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $urlCalendar = '/Plugins/Proyectos/node_modules/@fullcalendar/';
        AssetManager::add('css', FS_ROUTE.$urlCalendar . 'core/main.css');
        AssetManager::add('css', FS_ROUTE.$urlCalendar . 'daygrid/main.css');
        AssetManager::add('css', FS_ROUTE.$urlCalendar . 'bootstrap/main.min.css');
        AssetManager::add('js', FS_ROUTE.$urlCalendar . 'core/main.min.js');
        AssetManager::add('js', $this->setCalendarLang($urlCalendar, $user));
        AssetManager::add('js', FS_ROUTE.$urlCalendar . 'daygrid/main.min.js');
        AssetManager::add('js', FS_ROUTE.$urlCalendar . 'bootstrap/main.min.js');
    }
    
    private function setCalendarLang($urlCalendar, $user)
    {
        $pathCalendar = FS_FOLDER . str_replace("/", "\\", $urlCalendar);
        $pathCalendarLang = $pathCalendar . 'core\locales\\';
        
        $UserLang = strtolower(str_replace("_", "-", $user->langcode));
        $fileCalendarLang = $pathCalendarLang . $UserLang . '.js';
        
        if (!is_file($fileCalendarLang)) {
            $rest = substr($UserLang, 0, 2);
            $fileCalendarLang = $pathCalendarLang . $rest . '.js';
            
            if (is_file($fileCalendarLang)) {
                $this->CalendarLang = $rest;
            }
        } else {
            $this->CalendarLang = $UserLang;
        }
        
        return FS_ROUTE . $urlCalendar . 'core/locales/' . $this->CalendarLang . '.js';
    }
    
    public function getAllProjects()
    {
        $projects = new Proyecto();
        $where = [
            new DataBaseWhere('fechainicio', null, 'IS NOT'),
            new DataBaseWhere('fechafin', null, 'IS NOT'),
        ];
        return $projects->all($where);
    }
    
    public function getAllTaks()
    {
        $taks = new Tarea();
        $where = [
            new DataBaseWhere('fechainicio', null, 'IS NOT'),
            new DataBaseWhere('fechafin', null, 'IS NOT'),
        ];
        return $taks->all($where);
    }
}