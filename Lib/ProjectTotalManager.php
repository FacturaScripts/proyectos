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
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

/**
 * Description of ProjectTotalManager
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProjectTotalManager
{

    /**
     * 
     * @param int $idproyecto
     */
    public static function recalculate($idproyecto)
    {
        $project = new Proyecto();
        if (false === $project->loadFromCode($idproyecto)) {
            return;
        }

        $project->totalcompras = 0.0;
        foreach (static::purchaseInvoices($idproyecto) as $invoice) {
            $project->totalcompras += $invoice->total;
        }
        foreach (static::purchaseDeliveryNotes($idproyecto) as $delivery) {
            $project->totalcompras += $delivery->total;
        }
        foreach (static::purchaseOrders($idproyecto) as $order) {
            $project->totalcompras += $order->total;
        }

        $project->totalventas = 0.0;
        foreach (static::salesInvoices($idproyecto) as $invoice) {
            $project->totalventas += $invoice->total;
        }
        foreach (static::salesDeliveryNotes($idproyecto) as $delivery) {
            $project->totalventas += $delivery->total;
        }
        foreach (static::salesOrders($idproyecto) as $order) {
            $project->totalventas += $order->total;
        }

        $project->save();
    }

    /**
     * 
     * @param int $idproyecto
     *
     * @return AlbaranProveedor[]
     */
    protected static function purchaseDeliveryNotes($idproyecto)
    {
        $delivery = new AlbaranProveedor();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $delivery->all($where, [], 0, 0);
    }

    /**
     * 
     * @param int $idproyecto
     *
     * @return FacturaProveedor[]
     */
    protected static function purchaseInvoices($idproyecto)
    {
        $invoice = new FacturaProveedor();
        $where = [new DataBaseWhere('idproyecto', $idproyecto)];
        return $invoice->all($where, [], 0, 0);
    }

    /**
     * 
     * @param int $idproyecto
     *
     * @return PedidoProveedor[]
     */
    protected static function purchaseOrders($idproyecto)
    {
        $order = new PedidoProveedor();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $order->all($where, [], 0, 0);
    }

    /**
     * 
     * @param int $idproyecto
     *
     * @return AlbaranCliente[]
     */
    protected static function salesDeliveryNotes($idproyecto)
    {
        $delivery = new AlbaranCliente();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $delivery->all($where, [], 0, 0);
    }

    /**
     * 
     * @param int $idproyecto
     *
     * @return FacturaCliente[]
     */
    protected static function salesInvoices($idproyecto)
    {
        $invoice = new FacturaCliente();
        $where = [new DataBaseWhere('idproyecto', $idproyecto)];
        return $invoice->all($where, [], 0, 0);
    }

    /**
     * 
     * @param int $idproyecto
     *
     * @return PedidoCliente[]
     */
    protected static function salesOrders($idproyecto)
    {
        $order = new PedidoCliente();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('editable', true)
        ];
        return $order->all($where, [], 0, 0);
    }
}
