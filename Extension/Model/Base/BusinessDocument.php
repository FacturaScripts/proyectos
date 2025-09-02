<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Proyectos\Extension\Model\Base;

use Closure;
use FacturaScripts\Dinamic\Lib\ProjectStockManager;
use FacturaScripts\Dinamic\Lib\ProjectTotalManager;

/**
 * Description of BusinessDocument
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class BusinessDocument
{
    public function delete(): Closure
    {
        return function () {
            if ($this->idproyecto) {
                ProjectStockManager::rebuild($this->idproyecto);
                ProjectTotalManager::recalculate($this->idproyecto);
            }
        };
    }

    public function saveUpdate(): Closure
    {
        return function () {
            if ($this->idproyecto) {
                ProjectStockManager::rebuild($this->idproyecto);
                ProjectTotalManager::recalculate($this->idproyecto);
            }
            if ($this->getOriginal('idproyecto') && $this->getOriginal('idproyecto') != $this->idproyecto) {
                ProjectStockManager::rebuild($this->getOriginal('idproyecto'));
                ProjectTotalManager::recalculate($this->getOriginal('idproyecto'));
            }
        };
    }
}
