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

namespace FacturaScripts\Plugins\Proyectos\Extension\Model;

use Closure;

/**
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */

class Asiento
{
    public function saveInsertBefore(): Closure
    {
        return function () {
            $data = json_decode($_POST['data'] ?? '{}', true);
            if (array_key_exists('idproyecto', $data)) {
                $this->idproyecto = empty($data['idproyecto']) ? null : (int)$data['idproyecto'];
            }
        };
    }

    public function saveUpdateBefore(): Closure
    {
        return function () {
            $data = json_decode($_POST['data'] ?? '{}', true);
            if (array_key_exists('idproyecto', $data)) {
                $this->idproyecto = empty($data['idproyecto']) ? null : (int)$data['idproyecto'];
            }
        };
    }
}
