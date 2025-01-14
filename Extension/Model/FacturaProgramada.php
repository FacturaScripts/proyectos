<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Proyectos\Extension\Model;

use Closure;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Dinamic\Model\Proyecto;

class FacturaProgramada
{
    public function generate(): Closure
    {
        return function ($invoiceProg, $invoice) {
            if (empty($invoiceProg->idproyecto) || false === Plugins::isEnabled('Proyectos')) {
                return;
            }

            $project = new Proyecto();
            if ($project->loadFromCode($invoiceProg->idproyecto)) {
                $invoice->idproyecto = $project->idproyecto;
                return $invoice;
            }
        };
    }
}