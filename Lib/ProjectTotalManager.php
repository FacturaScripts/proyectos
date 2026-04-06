<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2022-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\Proyecto;

/**
 * Description of ProjectTotalManager
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProjectTotalManager
{
    public static function recalculate(int $idproyecto): void
    {
        $project = new Proyecto();
        if (false === $project->load($idproyecto)) {
            return;
        }

        // Compras
        $project->totalcompras = 0.0;
        $project->totalcompras += static::purchaseInvoices($idproyecto);
        $project->totalcompras += static::purchaseDeliveryNotes($idproyecto);
        $project->totalcompras += static::purchaseOrders($idproyecto);

        // Ventas
        $netoPresupuestos = 0.0;
        $netoPedidos = 0.0;
        $netoAlbaranes = 0.0;
        $netoFacturas = 0.0;

        $project->totalventas = 0.0;
        $project->totalventas += static::salesEstimations($idproyecto, $netoPresupuestos);
        $project->totalventas += static::salesOrders($idproyecto, $netoPedidos);
        $project->totalventas += static::salesDeliveryNotes($idproyecto, $netoAlbaranes);
        $project->totalventas += static::salesInvoices($idproyecto, $netoFacturas);

        $project->totalpendientefacturar = $netoPresupuestos + $netoPedidos + $netoAlbaranes;
        if ($project->totalpendientefacturar < 0) {
            $project->totalpendientefacturar = 0;
        }

        $project->save();
    }

    protected static function purchaseDeliveryNotes(int $idproyecto): float
    {
        $total = 0.0;
        $where = [Where::eq('idproyecto', $idproyecto)];
        foreach (AlbaranProveedor::all($where) as $delivery) {
            $childrens = $delivery->childrenDocuments();
            if (empty($childrens)) {
                $total += $delivery->total;
                continue;
            }

            $totalHijos = 0.0;
            foreach ($childrens as $child) {
                if ($child->idproyecto == $idproyecto) {
                    $totalHijos += $child->total;
                }
            }

            // si el hijo tiene un total mayor que el padre, restamos solo el total del padre
            $total += max(0.0, $delivery->total - $totalHijos);
        }

        return $total;
    }

    protected static function purchaseInvoices(int $idproyecto): float
    {
        $total = 0.0;
        $where = [Where::eq('idproyecto', $idproyecto)];
        foreach (FacturaProveedor::all($where) as $invoice) {
            $total += $invoice->total;
        }
        return $total;
    }

    protected static function purchaseOrders(int $idproyecto): float
    {
        $total = 0.0;
        $where = [Where::eq('idproyecto', $idproyecto)];
        foreach (PedidoProveedor::all($where) as $order) {
            $childrens = $order->childrenDocuments();
            if (empty($childrens)) {
                $total += $order->total;
                continue;
            }

            $totalHijos = 0.0;
            foreach ($childrens as $child) {
                if ($child->idproyecto == $idproyecto) {
                    $totalHijos += $child->total;
                }
            }

            // si el hijo tiene un total mayor que el padre, restamos solo el total del padre
            $total += max(0.0, $order->total - $totalHijos);
        }

        return $total;
    }

    protected static function salesDeliveryNotes(int $idproyecto, float &$neto): float
    {
        $total = 0.0;
        $neto = 0.0;
        $where = [Where::eq('idproyecto', $idproyecto)];
        foreach (AlbaranCliente::all($where) as $delivery) {
            $childrens = $delivery->childrenDocuments();
            if (empty($childrens)) {
                $total += $delivery->total;
                $neto += $delivery->neto;
                continue;
            }

            $totalHijos = 0.0;
            $netoHijos = 0.0;
            foreach ($childrens as $child) {
                if ($child->idproyecto == $idproyecto) {
                    $totalHijos += $child->total;
                    $netoHijos += $child->neto;
                }
            }

            // si el hijo tiene un total mayor que el padre, restamos solo el total del padre
            $total += max(0.0, $delivery->total - $totalHijos);
            $neto += max(0.0, $delivery->neto - $netoHijos);
        }

        return $total;
    }

    protected static function salesEstimations(int $idproyecto, float &$neto): float
    {
        $total = 0.0;
        $neto = 0.0;
        $where = [Where::eq('idproyecto', $idproyecto)];
        foreach (PresupuestoCliente::all($where) as $estimation) {
            $childrens = $estimation->childrenDocuments();
            if (empty($childrens)) {
                $total += $estimation->total;
                $neto += $estimation->neto;
                continue;
            }

            $totalHijos = 0.0;
            $netoHijos = 0.0;
            foreach ($childrens as $child) {
                if ($child->idproyecto == $idproyecto) {
                    $totalHijos += $child->total;
                    $netoHijos += $child->neto;
                }
            }

            // si el hijo tiene un total mayor que el padre, restamos solo el total del padre
            $total += max(0.0, $estimation->total - $totalHijos);
            $neto += max(0.0, $estimation->neto - $netoHijos);
        }

        return $total;
    }

    protected static function salesInvoices(int $idproyecto, float &$neto): float
    {
        $total = 0.0;
        $neto = 0.0;
        $where = [Where::eq('idproyecto', $idproyecto)];
        foreach (FacturaCliente::all($where) as $invoice) {
            $total += $invoice->total;
            $neto += $invoice->neto;
        }

        return $total;
    }

    protected static function salesOrders(int $idproyecto, float &$neto): float
    {
        $total = 0.0;
        $neto = 0.0;
        $where = [Where::eq('idproyecto', $idproyecto)];
        foreach (PedidoCliente::all($where) as $order) {
            $childrens = $order->childrenDocuments();
            if (empty($childrens)) {
                $total += $order->total;
                $neto += $order->neto;
                continue;
            }

            $totalHijos = 0.0;
            $netoHijos = 0.0;
            foreach ($childrens as $child) {
                if ($child->idproyecto == $idproyecto) {
                    $totalHijos += $child->total;
                    $netoHijos += $child->neto;
                }
            }

            // si el hijo tiene un total mayor que el padre, restamos solo el total del padre
            $total += max(0.0, $order->total - $totalHijos);
            $neto += max(0.0, $order->neto - $netoHijos);
        }

        return $total;
    }
}