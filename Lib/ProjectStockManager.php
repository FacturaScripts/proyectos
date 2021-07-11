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
namespace FacturaScripts\Plugins\Proyectos\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Plugins\Proyectos\Model\StockProyecto;

/**
 * Description of ProjectStockManager
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProjectStockManager
{

    /**
     * Recalculate the project stock.
     * 
     * @param int $idproyecto
     *
     * @return bool
     */
    public static function rebuild($idproyecto): bool
    {
        if (false === (bool) ToolBox::appSettings()->get('proyectos', 'stock', false)) {
            return true;
        }

        /// remove previous stock
        $projectStock = new StockProyecto();
        $projectStock->deleteFromProject($idproyecto);

        /// we initialice stock from every project document
        $stockData = [];
        $models = [
            new PresupuestoProveedor(), new PedidoProveedor(), new AlbaranProveedor(),
            new FacturaProveedor(), new PresupuestoCliente(), new PedidoCliente(),
            new AlbaranCliente(), new FacturaCliente()
        ];
        foreach ($models as $model) {
            $where = [new DataBaseWhere('idproyecto', $idproyecto)];
            foreach ($model->all($where, [], 0, 0) as $item) {
                $lines = $item->getLines();
                foreach ($lines as $line) {
                    static::setProjectStock($stockData, $line);
                }

                foreach ($item->childrenDocuments() as $child) {
                    static::setDeepProjectStock($stockData, $lines, $model->modelClassName(), $child);
                }
            }
        }

        /// now we save this data
        foreach ($stockData as $referencia => $data) {
            $stock = new StockProyecto();
            $where = [
                new DataBaseWhere('idproyecto', $idproyecto),
                new DataBaseWhere('referencia', $referencia)
            ];
            if (false === $stock->loadFromCode('', $where)) {
                $stock->idproducto = $data['idproducto'];
                $stock->idproyecto = $idproyecto;
                $stock->referencia = $referencia;
            }

            $stock->cantidad = $data['cantidad'];
            $stock->pterecibir = $data['pterecibir'];
            $stock->reservada = $data['reservada'];
            $stock->save();
        }

        return true;
    }

    /**
     * Find all related lines to calculate the stock.
     * 
     * @param array                  $stockData
     * @param BusinessDocumentLine[] $lines
     * @param string                 $model1
     * @param TransformerDocument    $child
     */
    protected static function setDeepProjectStock(&$stockData, $lines, $model1, $child)
    {
        /// when we group documents from different projects, the new document has idproyecto = null
        /// so we need to check this new document to calculate stock
        if (null !== $child->idproyecto) {
            return;
        }

        $childLines = $child->getLines();
        $childProjectLines = [];

        foreach ($lines as $line) {
            if (empty($line->referencia) || $line->getProducto()->nostock || false === $line->getProducto()->exists()) {
                continue;
            }

            $docTransformation = new DocTransformation();
            $where = [
                new DataBaseWhere('idlinea1', $line->primaryColumnValue()),
                new DataBaseWhere('model1', $model1)
            ];
            foreach ($docTransformation->all($where, [], 0, 0) as $dtl) {
                foreach ($childLines as $chLine) {
                    if ($chLine->primaryColumnValue() == $dtl->idlinea2) {
                        static::setProjectStock($stockData, $chLine);
                        $childProjectLines[] = $chLine;
                    }
                }
            }
        }

        /// we continue checking children
        if (!empty($childProjectLines)) {
            foreach ($child->childrenDocuments() as $grandChild) {
                static::setDeepProjectStock($stockData, $childProjectLines, $child->modelClassName(), $grandChild);
            }
        }
    }

    /**
     * 
     * @param array                $stockData
     * @param BusinessDocumentLine $line
     */
    protected static function setProjectStock(&$stockData, $line)
    {
        if (empty($line->referencia) || $line->getProducto()->nostock || false === $line->getProducto()->exists()) {
            return;
        } elseif (!isset($stockData[$line->referencia])) {
            $stockData[$line->referencia] = [
                'cantidad' => 0.0,
                'idproducto' => $line->idproducto,
                'pterecibir' => 0.0,
                'reservada' => 0.0
            ];
        }

        switch ($line->actualizastock) {
            case 1:
            case -1:
                $stockData[$line->referencia]['cantidad'] += $line->actualizastock * $line->cantidad;
                break;

            case 2:
                $stockData[$line->referencia]['pterecibir'] += $line->cantidad - $line->servido;
                break;

            case -2:
                $stockData[$line->referencia]['reservada'] += $line->cantidad - $line->servido;
                break;
        }
    }
}
