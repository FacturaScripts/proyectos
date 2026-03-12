<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProyectoPatternResetTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testAnnualPatternReset(): void
    {
        // activar ajuste
        Tools::settings('proyectos', 'reiniciarpatronanualmente', 1);
        Tools::settingsSave();

        $prevYear = date('Y') - 1;
        $patron = Tools::settings('proyectos', 'patron', 'PR-{ANYO}-{NUM}');
        $long = Tools::settings('proyectos', 'longnumero', 6);

        // crear proyecto del año anterior
        $old = new Proyecto();
        $old->nombre = strtr($patron, [
            '{ANYO}' => (string)$prevYear,
            '{ANYO2}' => substr((string)$prevYear, 2),
            '{MES}' => '01',
            '{DIA}' => '01',
            '{NUM}' => '5',
            '{0NUM}' => str_pad('5', $long, '0', STR_PAD_LEFT)
        ]);
        $old->fecha = $prevYear . '-01-01';
        $old->descripcion = 'old';
        $this->assertTrue($old->save());

        // crear proyecto nuevo (debe generarse y empezar en 1)
        $new = new Proyecto();
        $new->descripcion = 'nuevo';
        $this->assertTrue($new->save());

        $this->assertMatchesRegularExpression('/(\\d+)$/', $new->nombre, 'El nombre generado debe terminar en dígitos');
        preg_match('/(\\d+)$/', $new->nombre, $m);
        $this->assertEquals(1, intval($m[1]), 'Se esperaba que la numeración empiece en 1 para el ejercicio actual');

        // limpieza
        $this->assertTrue($new->delete());
        $this->assertTrue($old->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
