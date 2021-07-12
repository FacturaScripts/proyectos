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
namespace FacturaScripts\Plugins\Proyectos;

use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;

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
        $this->loadExtension(new Extension\Controller\EditProducto());
        $this->loadExtension(new Extension\Model\Base\BusinessDocument());
        $this->loadExtension(new Extension\Model\Stock());

        if (\class_exists('FacturaScripts\\Dinamic\\Controller\\Randomizer')) {
            $this->loadExtension(new Extension\Controller\Randomizer());
        }
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
