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

namespace FacturaScripts\Plugins\Proyectos;

use FacturaScripts\Core\Template\CronClass;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ProjectStockManager;
use FacturaScripts\Dinamic\Lib\ProjectTotalManager;
use FacturaScripts\Dinamic\Model\Proyecto;
use FacturaScripts\Dinamic\Model\Stock;

/**
 * Description of Cron
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Cron extends CronClass
{
    public function run(): void
    {
        $this->job('project-stock-update')
            ->every('1 month')
            ->run(function() {
                $projectModel = new Proyecto();
                foreach ($projectModel->all([], ['idproyecto' => 'DESC'], 0, 0) as $project) {
                    ProjectStockManager::rebuild($project->idproyecto);
                    ProjectTotalManager::recalculate($project->idproyecto);
                }

                $burnStock = (bool)Tools::settings('proyectos', 'burnstock', 0);
                if ($burnStock) {
                    $this->updateStock();
                }

                // limpiamos la caché para forzar refrescar los totales de los listados
                Cache::clear();
            });
    }

    protected function updateStock(): void
    {
        $stockModel = new Stock();
        $offset = 0;

        $stocks = $stockModel->all([], ['idstock' => 'ASC'], $offset);
        while (!empty($stocks)) {
            foreach ($stocks as $stock) {
                $stock->save();
                $offset++;
            }

            $stocks = $stockModel->all([], ['idstock' => 'ASC'], $offset);
        }
    }
}
