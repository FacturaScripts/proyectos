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

namespace FacturaScripts\Plugins\Proyectos\Extension\Model;

use Closure;
use FacturaScripts\Dinamic\Lib\ProjectAttachmentManager;

/**
 * Extensión del modelo AttachedFileRelation que mantiene el número de adjuntos (numdocs)
 * de los proyectos al añadir o quitar un adjunto, venga de la ficha del proyecto, de la API
 * o de cualquier otro sitio que use el modelo.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class AttachedFileRelation
{
    /**
     * Después de borrar la relación, recalcula los adjuntos de su proyecto.
     *
     * @return Closure
     */
    public function delete(): Closure
    {
        return function () {
            return $this->updateProjectNumDocs();
        };
    }

    /**
     * Después de crear la relación, recalcula los adjuntos de su proyecto.
     *
     * @return Closure
     */
    public function saveInsert(): Closure
    {
        return function () {
            return $this->updateProjectNumDocs();
        };
    }

    /**
     * Recalcula los adjuntos del proyecto de la relación, si es de un proyecto.
     * Devuelve siempre true: un fallo al recalcular no debe impedir guardar o borrar
     * el adjunto, y el Cron corrige después el contador.
     *
     * @return Closure
     */
    protected function updateProjectNumDocs(): Closure
    {
        return function () {
            if ($this->model !== ProjectAttachmentManager::MODEL_NAME) {
                return true;
            }

            $idproyecto = (int)(empty($this->modelid) ? $this->modelcode : $this->modelid);
            if ($idproyecto > 0) {
                ProjectAttachmentManager::recalculate($idproyecto);
            }

            return true;
        };
    }
}
