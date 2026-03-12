<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 */

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Plugins\Proyectos\Lib\ProjectTotalManager;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ProjectTotalManagerTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testInvoicedDeliveryNoteWithoutInvoiceInProject(): void
    {
        // Creamos dos proyectos: uno para el albarán y otro para la factura
        $project = new Proyecto();
        $project->nombre = 'Project A';
        $this->assertTrue($project->save(), 'can-not-save-project-a');

        $project2 = new Proyecto();
        $project2->nombre = 'Project B';
        $this->assertTrue($project2->save(), 'can-not-save-project-b');

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un albarán asociado al proyecto A
        $delivery = new AlbaranCliente();
        $this->assertTrue($delivery->setSubject($subject), 'can-not-set-subject-delivery');
        $delivery->idproyecto = $project->idproyecto;
        $this->assertTrue($delivery->save(), 'can-not-save-delivery');

        // añadimos una línea al albarán
        $line = $delivery->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-delivery-line');
        $lines = $delivery->getLines();
        $this->assertTrue(Calculator::calculate($delivery, $lines, true), 'can-not-calc-delivery');
        $delivery->reload();

        // marcamos el albarán como no editable (simulando que está facturado)
        foreach ($delivery->getAvailableStatus() as $status) {
            if (false === $status->editable) {
                $delivery->idestado = $status->idestado;
                break;
            }
        }
        $this->assertTrue($delivery->save(), 'can-not-set-non-editable-delivery');
        $this->assertFalse($delivery->editable, 'delivery-still-editable');

        // Generamos una factura a partir del albarán pero asignándola al proyecto B
        $generator = new \FacturaScripts\Core\Lib\BusinessDocumentGenerator();
        $this->assertTrue($generator->generate($delivery, 'FacturaCliente', [], [], ['idproyecto' => $project2->idproyecto]), 'can-not-generate-invoice-from-delivery');

        // obtenemos la factura generada
        $children = $delivery->childrenDocuments();
        $invoice = null;
        foreach ($children as $child) {
            if ($child instanceof FacturaCliente) {
                $invoice = $child;
                break;
            }
        }
        $this->assertNotNull($invoice, 'generated-invoice-not-found');
        $invoice->reload();

        // recuperamos la transacción creada
        $trans = new DocTransformation();
        $where = [
            new \FacturaScripts\Core\Where('model1', 'AlbaranCliente'),
            new \FacturaScripts\Core\Where('iddoc1', $delivery->idalbaran)
        ];
        $found = DocTransformation::all($where, [], 0, 0);
        $this->assertNotEmpty($found, 'doctrans-not-created');
        $trans = $found[0];

        // recalculamos los totales del proyecto A
        ProjectTotalManager::recalculate($project->idproyecto);

        $project->reload();

        // el total de ventas del proyecto debe incluir el total del albarán, ya que su factura está en otro proyecto
        $this->assertEquals($delivery->total, $project->totalventas, 'project-totalventas-bad');

        // limpieza: eliminamos la factura (hija) antes que el albarán
        $docTransModel = new DocTransformation();
        $docTransModel->deleteFrom('AlbaranCliente', $delivery->idalbaran, true);
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($delivery->delete(), 'can-not-delete-delivery');
        // Try to delete contact and subject, but don't fail the test if not possible
        try {
            $addr = $subject->getDefaultAddress();
            if ($addr) {
                @$addr->delete();
            }
        } catch (\Throwable $e) {
            // ignore
        }
        @$subject->delete();
        $this->assertTrue($project2->delete(), 'can-not-delete-project2');
        $this->assertTrue($project->delete(), 'can-not-delete-project1');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
