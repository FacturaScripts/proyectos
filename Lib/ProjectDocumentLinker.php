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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Dinamic\Lib\ProjectStockManager;
use FacturaScripts\Dinamic\Lib\ProjectTotalManager;

/**
 * Vincula y desvincula documentos de compra y venta y asientos contables de un proyecto.
 *
 * Escribe directamente la columna idproyecto, sin pasar por save(), igual que la ficha del
 * proyecto: así se pueden vincular documentos que ya no son editables (facturas emitidas,
 * presupuestos aceptados...) y asientos de ejercicios cerrados, cuyo save() lo rechazaría.
 * Después recalcula el stock y los totales del proyecto nuevo y del anterior.
 *
 * Lo usan la ficha del proyecto (EditProyecto) y la API (ApiVincularProyecto).
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProjectDocumentLinker
{
    /**
     * Modelos que se pueden vincular a un proyecto.
     *
     * @var string[]
     */
    const MODELS = [
        'PresupuestoCliente', 'PedidoCliente', 'AlbaranCliente', 'FacturaCliente',
        'PresupuestoProveedor', 'PedidoProveedor', 'AlbaranProveedor', 'FacturaProveedor',
        'Asiento',
    ];

    /**
     * Indica si el modelo se puede vincular a un proyecto.
     *
     * @param string $modelName nombre de la clase del modelo, sin namespace.
     *
     * @return bool
     */
    public static function isValidModel(string $modelName): bool
    {
        return in_array($modelName, self::MODELS, true);
    }

    /**
     * Vincula el registro al proyecto indicado o, con null, lo desvincula de su proyecto.
     *
     * @param ModelClass $record     documento o asiento ya cargado.
     * @param int|null   $idproyecto proyecto al que vincularlo; null para desvincularlo.
     *
     * @return bool false si el modelo no se puede vincular o falla la actualización.
     */
    public static function link(ModelClass $record, ?int $idproyecto): bool
    {
        if (false === static::isValidModel($record->modelClassName()) || false === $record->exists()) {
            return false;
        }

        $previous = empty($record->idproyecto) ? null : (int)$record->idproyecto;

        $db = new DataBase();
        $sql = 'UPDATE ' . $record->tableName()
            . ' SET idproyecto = ' . $db->var2str($idproyecto)
            . ' WHERE ' . $db->escapeColumn($record->primaryColumn()) . ' = ' . $db->var2str($record->id()) . ';';
        if (false === $db->exec($sql)) {
            return false;
        }

        // mantenemos el objeto sincronizado con la base de datos
        $record->idproyecto = $idproyecto;

        // recalculamos el proyecto nuevo y, si cambia, también el anterior
        foreach (array_unique(array_filter([$idproyecto, $previous])) as $id) {
            ProjectStockManager::rebuild($id);
            ProjectTotalManager::recalculate($id);
        }

        return true;
    }

    /**
     * Carga un documento o asiento vinculable por su clave primaria.
     *
     * @param string $modelName nombre de la clase del modelo, sin namespace.
     * @param mixed  $code      valor de la clave primaria.
     *
     * @return ModelClass|null null si el modelo no se puede vincular o el registro no existe.
     */
    public static function loadRecord(string $modelName, $code): ?ModelClass
    {
        if (false === static::isValidModel($modelName) || empty($code)) {
            return null;
        }

        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $record = new $modelClass();

        return $record->load($code) ? $record : null;
    }
}
