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

namespace FacturaScripts\Plugins\Proyectos\Lib;

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\Proyecto;

/**
 * Mantiene la columna numdocs de los proyectos con el número de archivos adjuntos.
 *
 * Se llama al añadir o quitar un adjunto (extensión del modelo AttachedFileRelation),
 * una sola vez al actualizar el plugin (migración) y periódicamente desde el Cron,
 * para corregir los adjuntos que se hayan creado o borrado por otras vías.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProjectAttachmentManager
{
    /** Nombre del modelo con el que se guardan las relaciones de adjuntos de los proyectos. */
    const MODEL_NAME = 'Proyecto';

    /**
     * Cuenta los archivos adjuntos de un proyecto.
     *
     * @param int $idproyecto Identificador del proyecto.
     *
     * @return int
     */
    public static function count(int $idproyecto): int
    {
        return AttachedFileRelation::count([
            Where::eq('model', self::MODEL_NAME),
            Where::eq('modelid|modelcode', $idproyecto)
        ]);
    }

    /**
     * Recalcula el número de adjuntos de un proyecto y solo lo guarda si ha cambiado.
     * Actualiza únicamente la columna numdocs, sin pasar por test() ni por save(),
     * para no modificar el resto de datos del proyecto.
     *
     * @param int $idproyecto Identificador del proyecto.
     *
     * @return bool Devuelve false si el proyecto no existe o no se ha podido guardar.
     */
    public static function recalculate(int $idproyecto): bool
    {
        $project = new Proyecto();
        if (false === $project->load($idproyecto)) {
            return false;
        }

        $num = static::count($idproyecto);
        if ($num === (int)$project->numdocs) {
            return true;
        }

        return $project->update(['numdocs' => $num]);
    }

    /**
     * Recalcula el número de adjuntos de todos los proyectos, por lotes para no cargarlos
     * todos en memoria.
     *
     * @param int $limit Número de proyectos por lote.
     */
    public static function recalculateAll(int $limit = 100): void
    {
        $offset = 0;
        $orderBy = ['idproyecto' => 'ASC'];
        $projects = Proyecto::all([], $orderBy, $offset, $limit);
        while (false === empty($projects)) {
            foreach ($projects as $project) {
                static::recalculate($project->idproyecto);
            }

            $offset += $limit;
            $projects = Proyecto::all([], $orderBy, $offset, $limit);
        }
    }
}
