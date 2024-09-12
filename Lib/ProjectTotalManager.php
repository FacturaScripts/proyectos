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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

/**
 * Description of ProjectTotalManager
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProjectTotalManager
{
    public static function recalculate(int $idproyecto)
    {
        $project = new Proyecto();
        if (false === $project->loadFromCode($idproyecto)) {
            return;
        }

        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];

        $project->albaranes_compra = 0.0;
        $project->albaranes_venta = 0.0;
        $project->facturas_compra = 0.0;
        $project->facturas_venta = 0.0;
        $project->pedidos_compra = 0.0;
        $project->pedidos_venta = 0.0;
        $project->presupuesto_venta = 0.0;
        $project->presupuestos_compra = 0.0;

	//pedidos o albaranes de compra
        $project->previsioncompras = 0.0;

	//presupuestos, pedidos o albaranes de venta
        $project->previsionventas = 0.0;

        $project->totalcompras = 0.0;

        $project->totalventas = 0.0;

	//Presupuestos de compra
        foreach (static::purchaseEstimations($idproyecto) as $estimation) {
            $project->previsioncompras += $estimation->neto;
	    $project->presupuestos_compra += $estimation->neto;
        }

	//Pedido de compra
        foreach (static::purchaseOrders($idproyecto) as $order) {
            $project->previsioncompras += $order->neto;
	    $project->pedidos_compra += $order->neto;
        }


	//Albaran de compra
        foreach (static::purchaseDeliveryNotes($idproyecto) as $delivery) {
            $project->previsioncompras += $delivery->neto;
	    $project->albaranes_compra += $delivery->neto;
        }


	//Factura de compra
        foreach (static::purchaseInvoices($idproyecto) as $invoice) {
            $project->totalcompras += $invoice->neto;
	    $project->facturas_compra += $invoice->neto;
        }

        $netoPresupuestos = 0.0;
        $netoPedidos = 0.0;
        $netoAlbaranes = 0.0;
        $netoFacturas = 0.0;


	//Presupuestos de venta
        foreach (static::salesEstimations($idproyecto) as $estimation) {
            $project->previsionventas += $estimation->neto;
            $netoPresupuestos += $estimation->neto;
	    $project->presupuesto_venta += $estimation->neto;
        }

	//Pedidos de venta
        foreach (static::salesOrders($idproyecto) as $order) {
            $project->previsionventas += $order->neto;
            $netoPedidos += $order->neto;
	    $project->pedidos_venta += $order->neto;
        }

	//Albaran de venta
        foreach (static::salesDeliveryNotes($idproyecto) as $delivery) {
            $project->previsionventas += $delivery->neto;
            $netoAlbaranes += $delivery->neto;
//						if($delivery && $delivery->net)
//										$project->albaranes_venta += $delivery->net;
        }

	//Facturas de venta
        foreach (static::salesInvoices($idproyecto) as $invoice) {
            $project->totalventas += $invoice->neto;
            $netoFacturas += $invoice->neto;
	    $project->facturas_venta += $invoice->neto;
        }


        $project->totalpendientefacturar = ($netoPresupuestos + $netoPedidos + $netoAlbaranes) - $netoFacturas;

        if ($project->totalpendientefacturar < 0) {
            $project->totalpendientefacturar = 0;
        }

        $project->beneficioprevisto = $project->previsionventas - $project->previsioncompras;
        $project->beneficiobruto = $project->totalventas - $project->totalcompras;

        $project->save();
    }
    /**
     * @param int $idproyecto
     * @return AlbaranProveedor[]
     */
    public static function purchaseDeliveryNotes(int $idproyecto): array
    {
        $delivery = new AlbaranProveedor();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $delivery->all($where, [], 0, 0);
    }

    /**
     * @param int $idproyecto

    /**
     * @param int $idproyecto
     *
     * @return PresupuestoProveedor[]
     */
    public static function purchaseEstimations($idproyecto): array
    {
        $presupuesto = new PresupuestoProveedor();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $presupuesto->all($where, [], 0, 0);
    }

    /**
     * @param int $idproyecto
     * @return FacturaProveedor[]
     */
    public static function purchaseInvoices(int $idproyecto): array
    {
        $invoice = new FacturaProveedor();
        $where = [
		new DataBaseWhere('idproyecto', $idproyecto),
//		new DataBaseWhere('editable', true)
	];
        return $invoice->all($where, [], 0, 0);
    }

    /**
     * @param int $idproyecto
     * @return PedidoProveedor[]
     */
    public static function purchaseOrders(int $idproyecto): array
    {
        $order = new PedidoProveedor();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $order->all($where, [], 0, 0);
    }

    /**
     * @param int $idproyecto
     * @return AlbaranCliente[]
     */
    public static function salesDeliveryNotes(int $idproyecto): array
    {
        $delivery = new AlbaranCliente();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $delivery->all($where, [], 0, 0);
    }

    /**
     * @param int $idproyecto
     * @return PresupuestoCliente[]
     */
    public static function salesEstimations(int $idproyecto): array
    {
        $estimation = new PresupuestoCliente();
        $where = [
		new DataBaseWhere('idproyecto', $idproyecto),
		new DataBaseWhere('editable', true)
	];
        return $estimation->all($where, [], 0, 0);
    }

    /**
     * @param int $idproyecto
     * @return FacturaCliente[]
     */
    public static function salesInvoices(int $idproyecto): array
    {
        $invoice = new FacturaCliente();
        $where = [
		new DataBaseWhere('idproyecto', $idproyecto),
//new DataBaseWhere('editable', true)
	];
        return $invoice->all($where, [], 0, 0);
    }

    /**
     * @param int $idproyecto
     * @return PedidoCliente[]
     */
    public static function salesOrders(int $idproyecto): array
    {
        $order = new PedidoCliente();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $order->all($where, [], 0, 0);
    }



}
