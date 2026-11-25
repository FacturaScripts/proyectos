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

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\ProjectDocumentLinker;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PagoCliente;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Proyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de ProjectDocumentLinker: vincular y desvincular documentos (también los no
 * editables) y asientos de un proyecto, recalculando sus totales.
 */
final class ProjectDocumentLinkerTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /**
     * Prepara la configuración por defecto y las tablas que consultan los recibos.
     */
    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();

        // las tablas de pagos y partidas deben existir antes de borrar facturas y asientos: se
        // consultan dentro de una transacción y en PostgreSQL no se pueden crear ahí
        new PagoCliente();
        new Partida();
    }

    /**
     * Los asientos se vinculan y desvinculan igual que los documentos.
     */
    public function testLinkAccountingEntry(): void
    {
        $project = new Proyecto();
        $this->assertTrue($project->save(), 'project-cant-save');

        $entry = new Asiento();
        $entry->concepto = 'Test ProjectDocumentLinker';
        $this->assertTrue($entry->save(), 'entry-cant-save');

        $record = ProjectDocumentLinker::loadRecord('Asiento', $entry->idasiento);
        $this->assertNotNull($record, 'entry-not-loaded');
        $this->assertTrue(ProjectDocumentLinker::link($record, (int)$project->idproyecto), 'entry-cant-link');
        $entry->reload();
        $this->assertEquals($project->idproyecto, $entry->idproyecto, 'entry-not-linked');

        $this->assertTrue(ProjectDocumentLinker::link($entry, null), 'entry-cant-unlink');
        $entry->reload();
        $this->assertNull($entry->idproyecto, 'entry-not-unlinked');

        // limpiamos
        $this->assertTrue($entry->delete(), 'entry-cant-delete');
        $this->assertTrue($project->delete(), 'project-cant-delete');
    }

    /**
     * Una factura no editable no se puede vincular con save(), pero sí con el linker,
     * que además recalcula los totales del proyecto nuevo y del anterior.
     */
    public function testLinkNonEditableInvoice(): void
    {
        // creamos dos proyectos
        $project1 = new Proyecto();
        $this->assertTrue($project1->save(), 'project-1-cant-save');
        $project2 = new Proyecto();
        $this->assertTrue($project2->save(), 'project-2-cant-save');

        // creamos una factura y la pasamos a un estado no editable
        $invoice = $this->getRandomCustomerInvoice();
        $invoice->idestado = $this->getInvoiceStatus(false);
        $this->assertTrue($invoice->save(), 'invoice-cant-lock');
        $this->assertFalse($invoice->editable, 'invoice-still-editable');

        // la API genérica no puede cambiar el proyecto de una factura bloqueada
        $invoice->idproyecto = $project1->idproyecto;
        $this->assertFalse($invoice->save(), 'locked-invoice-saved');
        $invoice->reload();

        // el linker sí puede
        $record = ProjectDocumentLinker::loadRecord('FacturaCliente', $invoice->idfactura);
        $this->assertNotNull($record, 'invoice-not-loaded');
        $this->assertTrue(ProjectDocumentLinker::link($record, (int)$project1->idproyecto), 'invoice-cant-link');
        $invoice->reload();
        $this->assertEquals($project1->idproyecto, $invoice->idproyecto, 'invoice-not-linked');
        $project1->reload();
        $this->assertEquals($invoice->total, $project1->totalventas, 'project-1-total-not-updated');

        // la movemos al segundo proyecto: se recalculan los dos
        $this->assertTrue(ProjectDocumentLinker::link($invoice, (int)$project2->idproyecto), 'invoice-cant-move');
        $project1->reload();
        $project2->reload();
        $this->assertEquals(0.0, (float)$project1->totalventas, 'project-1-total-not-cleared');
        $this->assertEquals($invoice->total, $project2->totalventas, 'project-2-total-not-updated');

        // la desvinculamos
        $this->assertTrue(ProjectDocumentLinker::link($invoice, null), 'invoice-cant-unlink');
        $invoice->reload();
        $this->assertNull($invoice->idproyecto, 'invoice-not-unlinked');
        $project2->reload();
        $this->assertEquals(0.0, (float)$project2->totalventas, 'project-2-total-not-cleared');

        // limpiamos: la factura vuelve a un estado editable para poder borrarla
        $invoice->idestado = $this->getInvoiceStatus(true);
        $this->assertTrue($invoice->save(), 'invoice-cant-unlock');
        $customer = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'invoice-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
        $this->assertTrue($project1->delete(), 'project-1-cant-delete');
        $this->assertTrue($project2->delete(), 'project-2-cant-delete');
    }

    /**
     * Solo se aceptan los documentos de compra y venta y los asientos.
     */
    public function testValidModels(): void
    {
        $this->assertTrue(ProjectDocumentLinker::isValidModel('FacturaCliente'));
        $this->assertTrue(ProjectDocumentLinker::isValidModel('Asiento'));
        $this->assertFalse(ProjectDocumentLinker::isValidModel('Cliente'));
        $this->assertFalse(ProjectDocumentLinker::isValidModel(''));
        $this->assertNull(ProjectDocumentLinker::loadRecord('Cliente', '1'));
        $this->assertNull(ProjectDocumentLinker::loadRecord('FacturaCliente', ''));
    }

    /**
     * Devuelve un estado de factura de cliente editable o no editable.
     *
     * @param bool $editable true para un estado editable, false para uno bloqueado.
     *
     * @return int id del estado.
     */
    private function getInvoiceStatus(bool $editable): int
    {
        $status = new EstadoDocumento();
        $where = [Where::eq('tipodoc', 'FacturaCliente'), Where::eq('editable', $editable)];
        $this->assertTrue($status->loadWhere($where), 'status-not-found');

        return (int)$status->idestado;
    }

    /**
     * Restaura los errores registrados al terminar cada prueba.
     */
    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
