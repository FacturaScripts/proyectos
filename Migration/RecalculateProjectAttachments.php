<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Proyectos\Migration;

use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Dinamic\Lib\ProjectAttachmentManager;

/**
 * Rellena una sola vez la columna numdocs de los proyectos que ya existían al añadirla.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class RecalculateProjectAttachments extends MigrationClass
{
    const MIGRATION_NAME = 'recalculate_project_attachments_2026_11';

    /**
     * Recalcula los adjuntos de todos los proyectos.
     */
    public function run(): void
    {
        if (false === $this->db()->tableExists('proyectos') || false === $this->db()->tableExists('attached_files_rel')) {
            return;
        }

        ProjectAttachmentManager::recalculateAll();
    }
}
