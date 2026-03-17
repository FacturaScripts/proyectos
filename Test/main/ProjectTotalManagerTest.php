<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 */

namespace FacturaScripts\Test\Plugins;

use Exception;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;
use Throwable;

final class ProjectTotalManagerTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    protected array $createdProjects = [];

    protected array $createdCustomers = [];

    protected array $createdSuppliers = [];

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testTotalCalculate(): void
    {
        // creamos un proyecto
        $project = new Proyecto();
        $this->assertTrue($project->save(), 'No se pudo guardar el proyecto');
        $this->trackProject($project);

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'No se pudo guardar el cliente');
        $this->trackCustomer($customer);

        // creamos el presupuesto de cliente 1
        $estimation1 = $this->getRandomCustomerEstimation($customer);

        // vinculamos el proyecto al presupuesto de cliente 1
        $estimation1->idproyecto = $project->id();
        $this->assertTrue($estimation1->save(), 'No se pudo guardar el presupuesto 1');
        $linesEstimation1 = $estimation1->getLines();

        // validamos el proyecto
        $this->checkProject($project, 0, 181.5, 150);

        // Generamos una factura a partir del presupuesto de cliente 1 solo con la línea 1
        $generator = new BusinessDocumentGenerator();
        $this->assertTrue($generator->generate(
            $estimation1,
            'FacturaCliente',
            [$linesEstimation1[0]],
            [$linesEstimation1[0]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-estimation-1');

        // validamos proyecto
        $this->checkProject($project, 0, 181.5, 50);

        // Generamos una factura a partir del presupuesto de cliente 1 solo con la línea 2
        $this->assertTrue($generator->generate(
            $estimation1,
            'FacturaCliente',
            [$linesEstimation1[1]],
            [$linesEstimation1[1]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-estimation-1-line-2');

        // validamos proyecto
        $this->checkProject($project, 0, 181.5, 0);

        // creamos el pedido de cliente 1
        $order1 = $this->getRandomCustomerOrder($customer);

        // vinculamos el proyecto al pedido de cliente 1
        $order1->idproyecto = $project->id();
        $this->assertTrue($order1->save(), 'No se pudo guardar el pedido 1');
        $linesOrder1 = $order1->getLines();

        // validamos el proyecto
        $this->checkProject($project, 0, 363, 150);

        // Generamos una factura a partir del pedido de cliente 1 solo con la línea 1
        $this->assertTrue($generator->generate(
            $order1,
            'FacturaCliente',
            [$linesOrder1[0]],
            [$linesOrder1[0]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-order-1');

        // validamos proyecto
        $this->checkProject($project, 0, 363, 50);

        // Generamos una factura a partir del pedido de cliente 1 solo con la línea 2
        $this->assertTrue($generator->generate(
            $order1,
            'FacturaCliente',
            [$linesOrder1[1]],
            [$linesOrder1[1]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-order-1-line-2');

        // validamos proyecto
        $this->checkProject($project, 0, 363, 0);

        // creamos el albarán de cliente 1
        $deliveryNote1 = $this->getRandomCustomerDeliveryNote($customer);

        // vinculamos el proyecto al albarán de cliente 1
        $deliveryNote1->idproyecto = $project->id();
        $this->assertTrue($deliveryNote1->save(), 'No se pudo guardar el albarán 1');
        $linesDeliveryNote1 = $deliveryNote1->getLines();

        // validamos el proyecto
        $this->checkProject($project, 0, 544.5, 150);

        // Generamos una factura a partir del albarán de cliente 1 solo con la línea 1
        $this->assertTrue($generator->generate(
            $deliveryNote1,
            'FacturaCliente',
            [$linesDeliveryNote1[0]],
            [$linesDeliveryNote1[0]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-delivery-note-1');

        // validamos proyecto
        $this->checkProject($project, 0, 544.5, 50);

        // Generamos una factura a partir del albarán de cliente 1 solo con la línea 2
        $this->assertTrue($generator->generate(
            $deliveryNote1,
            'FacturaCliente',
            [$linesDeliveryNote1[1]],
            [$linesDeliveryNote1[1]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-delivery-note-1-line-2');

        // validamos proyecto
        $this->checkProject($project, 0, 544.5, 0);

        // Creamos la factura de cliente 1
        $invoice1 = $this->getRandomCustomerInvoice($customer);

        // Vinculamos la factura de cliente al proyecto
        $invoice1->idproyecto = $project->id();
        $this->assertTrue($invoice1->save(), 'No se pudo guardar la factura 1');

        // validamos proyecto
        $this->checkProject($project, 0, 726, 0);

        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'No se pudo guardar el proveedor');
        $this->trackSupplier($supplier);

        // creamos el pedido de proveedor 1
        $purchaseOrder1 = $this->getRandomSupplierOrder($supplier);

        // vinculamos el proyecto al pedido de proveedor 1
        $purchaseOrder1->idproyecto = $project->id();
        $this->assertTrue($purchaseOrder1->save(), 'No se pudo guardar el pedido de proveedor 1');
        $linesPurchaseOrder1 = $purchaseOrder1->getLines();

        // validamos el proyecto
        $this->checkProject($project, 181.5, 726, 0);

        // Generamos una factura a partir del pedido de proveedor 1 solo con la línea 1
        $this->assertTrue($generator->generate(
            $purchaseOrder1,
            'FacturaProveedor',
            [$linesPurchaseOrder1[0]],
            [$linesPurchaseOrder1[0]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-purchase-order-1');

        // validamos proyecto
        $this->checkProject($project, 181.5, 726, 0);

        // Generamos una factura a partir del pedido de proveedor 1 solo con la línea 2
        $this->assertTrue($generator->generate(
            $purchaseOrder1,
            'FacturaProveedor',
            [$linesPurchaseOrder1[1]],
            [$linesPurchaseOrder1[1]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-purchase-order-1-line-2');

        // validamos proyecto
        $this->checkProject($project, 181.5, 726, 0);

        // creamos el albarán de proveedor 1
        $supplierDeliveryNote1 = $this->getRandomSupplierDeliveryNote($supplier);

        // vinculamos el proyecto al albarán de proveedor 1
        $supplierDeliveryNote1->idproyecto = $project->id();
        $this->assertTrue($supplierDeliveryNote1->save(), 'No se pudo guardar el albarán de proveedor 1');
        $linesSupplierDeliveryNote1 = $supplierDeliveryNote1->getLines();

        // validamos el proyecto
        $this->checkProject($project, 363, 726, 0);

        // Generamos una factura a partir del albarán de proveedor 1 solo con la línea 1
        $this->assertTrue($generator->generate(
            $supplierDeliveryNote1,
            'FacturaProveedor',
            [$linesSupplierDeliveryNote1[0]],
            [$linesSupplierDeliveryNote1[0]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-supplier-delivery-note-1');

        // validamos proyecto
        $this->checkProject($project, 363, 726, 0);

        // Generamos una factura a partir del albarán de proveedor 1 solo con la línea 2
        $this->assertTrue($generator->generate(
            $supplierDeliveryNote1,
            'FacturaProveedor',
            [$linesSupplierDeliveryNote1[1]],
            [$linesSupplierDeliveryNote1[1]->idlinea => 1],
            ['idproyecto' => $project->id()]
        ), 'can-not-generate-invoice-from-supplier-delivery-note-1-line-2');

        // validamos proyecto
        $this->checkProject($project, 363, 726, 0);

        // Creamos la factura de proveedor 1
        $supplierInvoice1 = $this->getRandomSupplierInvoice($supplier);

        // Vinculamos la factura de proveedor al proyecto
        $supplierInvoice1->idproyecto = $project->id();
        $this->assertTrue($supplierInvoice1->save(), 'No se pudo guardar la factura de proveedor 1');

        // validamos proyecto
        $this->checkProject($project, 544.5, 726, 0);

        $this->cleanupCreatedEntities();
    }

    protected function checkProject(Proyecto $project, float $totalCompras, float $totalVentas, float $totalPendiente): void
    {
        try {
            $project->reload();
            $this->assertEquals($totalCompras, $project->totalcompras, 'Total compras no coincide ' . $totalCompras . ' - ' . $project->totalcompras);
            $this->assertEquals($totalVentas, $project->totalventas, 'Total ventas no coincide ' . $totalVentas . ' - ' . $project->totalventas);
            $this->assertEquals($totalPendiente, $project->totalpendientefacturar, 'Total pendiente no coincide ' . $totalPendiente . ' - ' . $project->totalpendientefacturar);
        } catch (Throwable $error) {
            $this->cleanupCreatedEntities();
            throw $error;
        }
    }

    protected function cleanupCreatedEntities(): void
    {
        foreach ($this->createdProjects as $project) {
            try {
                $where = [Where::eq('idproyecto', $project->id())];
                foreach (FacturaCliente::all($where) as $invoice) {
                    $invoice->delete();
                }
                foreach (FacturaProveedor::all($where) as $invoice) {
                    $invoice->delete();
                }
                foreach (AlbaranCliente::all($where) as $delivery) {
                    $delivery->delete();
                }
                foreach (AlbaranProveedor::all($where) as $delivery) {
                    $delivery->delete();
                }
                foreach (PedidoCliente::all($where) as $order) {
                    $order->delete();
                }
                foreach (PedidoProveedor::all($where) as $order) {
                    $order->delete();
                }
                foreach (PresupuestoCliente::all($where) as $estimation) {
                    $estimation->delete();
                }

                $project->delete();
            } catch (Throwable $error) {
                // Best-effort cleanup for failed assertions.
            }
        }

        foreach ($this->createdCustomers as $customer) {
            try {
                $customer->delete();
            } catch (Throwable $error) {
                // Best-effort cleanup for failed assertions.
            }
        }

        foreach ($this->createdSuppliers as $supplier) {
            try {
                $supplier->delete();
            } catch (Throwable $error) {
                // Best-effort cleanup for failed assertions.
            }
        }

        $this->createdProjects = [];
        $this->createdCustomers = [];
        $this->createdSuppliers = [];
    }

    protected function getRandomCustomer(string $test_name = ''): Cliente
    {
        $cliente = new Cliente();
        $cliente->cifnif = 'B' . mt_rand(1, 999999);
        $cliente->nombre = 'Customer Rand ' . mt_rand(1, 99999);
        $cliente->observaciones = $test_name;
        $cliente->razonsocial = 'Empresa ' . mt_rand(1, 99999);

        return $cliente;
    }

    protected function getRandomCustomerDeliveryNote(Cliente $customer): AlbaranCliente
    {
        $deliveryNote = new AlbaranCliente();
        $deliveryNote->setSubject($customer);
        if (false === $deliveryNote->save()) {
            throw new Exception('Failed to save random delivery note');
        }

        $line = $deliveryNote->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        if (false === $line->save()) {
            throw new Exception('Failed to save random delivery note line 1');
        }

        $line = $deliveryNote->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        if (false === $line->save()) {
            throw new Exception('Failed to save random delivery note line 2');
        }

        $lines = $deliveryNote->getLines();
        if (false === Calculator::calculate($deliveryNote, $lines, true)) {
            throw new Exception('Failed to calculate random delivery note');
        }

        return $deliveryNote;
    }

    protected function getRandomCustomerEstimation(Cliente $customer): PresupuestoCliente
    {
        $estimation = new PresupuestoCliente();
        $estimation->setSubject($customer);
        if (false === $estimation->save()) {
            throw new Exception('Failed to save random invoice');
        }

        $line = $estimation->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        if (false === $line->save()) {
            throw new Exception('Failed to save random estimation line 1');
        }

        $line = $estimation->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        if (false === $line->save()) {
            throw new Exception('Failed to save random estimation line 2');
        }

        $lines = $estimation->getLines();
        if (false === Calculator::calculate($estimation, $lines, true)) {
            throw new Exception('Failed to calculate random estimation');
        }

        return $estimation;
    }

    protected function getRandomCustomerInvoice(Cliente $customer): FacturaCliente
    {
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        if (false === $invoice->save()) {
            throw new Exception('Failed to save random invoice');
        }

        $line = $invoice->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        if (false === $line->save()) {
            throw new Exception('Failed to save random invoice line 1');
        }

        $line = $invoice->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        if (false === $line->save()) {
            throw new Exception('Failed to save random invoice line 2');
        }

        $lines = $invoice->getLines();
        if (false === Calculator::calculate($invoice, $lines, true)) {
            throw new Exception('Failed to calculate random invoice');
        }

        return $invoice;
    }

    protected function getRandomCustomerOrder(Cliente $customer): PedidoCliente
    {
        $order = new PedidoCliente();
        $order->setSubject($customer);
        if (false === $order->save()) {
            throw new Exception('Failed to save random order');
        }

        $line = $order->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        if (false === $line->save()) {
            throw new Exception('Failed to save random order line 1');
        }

        $line = $order->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        if (false === $line->save()) {
            throw new Exception('Failed to save random order line 2');
        }

        $lines = $order->getLines();
        if (false === Calculator::calculate($order, $lines, true)) {
            throw new Exception('Failed to calculate random order');
        }

        return $order;
    }

    protected function getRandomSupplier(string $test_name = ''): Proveedor
    {
        $proveedor = new Proveedor();
        $proveedor->cifnif = mt_rand(1, 99999999) . 'J';
        $proveedor->nombre = 'Proveedor Rand ' . mt_rand(1, 999);
        $proveedor->observaciones = $test_name;
        $proveedor->razonsocial = 'Empresa ' . mt_rand(1, 999);

        return $proveedor;
    }

    protected function getRandomSupplierDeliveryNote(Proveedor $supplier): AlbaranProveedor
    {
        $deliveryNote = new AlbaranProveedor();
        $deliveryNote->setSubject($supplier);
        if (false === $deliveryNote->save()) {
            throw new Exception('Failed to save random supplier delivery note');
        }

        $line = $deliveryNote->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        if (false === $line->save()) {
            throw new Exception('Failed to save random supplier delivery note line 1');
        }

        $line = $deliveryNote->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        if (false === $line->save()) {
            throw new Exception('Failed to save random supplier delivery note line 2');
        }

        $lines = $deliveryNote->getLines();
        if (false === Calculator::calculate($deliveryNote, $lines, true)) {
            throw new Exception('Failed to calculate random supplier delivery note');
        }

        return $deliveryNote;
    }

    protected function getRandomSupplierInvoice(Proveedor $supplier): FacturaProveedor
    {
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        if (false === $invoice->save()) {
            throw new Exception('Failed to save random supplier invoice');
        }

        $line = $invoice->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        if (false === $line->save()) {
            throw new Exception('Failed to save random supplier invoice line 1');
        }

        $line = $invoice->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        if (false === $line->save()) {
            throw new Exception('Failed to save random supplier invoice line 2');
        }

        $lines = $invoice->getLines();
        if (false === Calculator::calculate($invoice, $lines, true)) {
            throw new Exception('Failed to calculate random supplier invoice');
        }

        return $invoice;
    }

    protected function getRandomSupplierOrder(Proveedor $supplier): PedidoProveedor
    {
        $order = new PedidoProveedor();
        $order->setSubject($supplier);
        if (false === $order->save()) {
            throw new Exception('Failed to save random supplier order');
        }

        $line = $order->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        if (false === $line->save()) {
            throw new Exception('Failed to save random supplier order line 1');
        }

        $line = $order->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        if (false === $line->save()) {
            throw new Exception('Failed to save random supplier order line 2');
        }

        $lines = $order->getLines();
        if (false === Calculator::calculate($order, $lines, true)) {
            throw new Exception('Failed to calculate random supplier order');
        }

        return $order;
    }

    protected function trackProject(Proyecto $project): void
    {
        $this->createdProjects[] = $project;
    }

    protected function trackCustomer(Cliente $customer): void
    {
        $this->createdCustomers[] = $customer;
    }

    protected function trackSupplier(Proveedor $supplier): void
    {
        $this->createdSuppliers[] = $supplier;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
