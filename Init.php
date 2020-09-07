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
namespace FacturaScripts\Plugins\Proyectos;

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Dinamic\Model\Role;
use FacturaScripts\Dinamic\Model\RoleAccess;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Init extends InitClass
{

    public function init()
    {
        $this->loadExtension(new Extension\Controller\DocumentStitcher());
        $this->loadExtension(new Extension\Controller\EditCliente());
        $this->loadExtension(new Extension\Model\Base\BusinessDocument());
    }

    public function update()
    {
        /// init models
        new Model\UserProyecto();
        new AlbaranCliente();
        new AlbaranProveedor();
        new FacturaCliente();
        new FacturaProveedor();
        new PedidoCliente();
        new PedidoProveedor();
        new PresupuestoCliente();
        new PresupuestoProveedor();

        $this->setupSettings();
        $this->createProjectRole();
    }

    private function createProjectRole()
    {
        $role = new Role();
        if ($role->loadFromCode('proyectos')) {
            return;
        }
        $role->codrole = 'proyectos';
        $role->descripcion = 'Proyectos';

        $dataBase = new DataBase();
        $dataBase->beginTransaction();
        try {
            if ($role->save()) {
                $access = new RoleAccess();

                $listAccess = [
                    'EditNotaProyecto',
                    'EditTareaProyecto',
                    'ListTareaProyecto',
                    'EditFaseTarea',
                    'EditEstadoProyecto',
                    'ListProyecto',
                    'EditProyecto'
                ];

                foreach ($listAccess as $list) {
                    $access->clear();
                    $access->allowdelete = 1;
                    $access->allowupdate = 1;
                    $access->codrole = $role->codrole;
                    $access->pagename = $list;
                    $access->save();
                }
            }
            $dataBase->commit();
        } catch (Exception $e) {
            $dataBase->rollback();
        }
    }

    private function setupSettings()
    {
        $appsettings = $this->toolBox()->appSettings();
        $patron = $appsettings->get('proyectos', 'patron', 'PR-{ANYO}-{NUM}');
        $longnumero = $appsettings->get('proyectos', 'longnumero', 6);

        $appsettings->set('proyectos', 'patron', $patron);
        $appsettings->set('proyectos', 'longnumero', $longnumero);
        $appsettings->save();
    }
}
