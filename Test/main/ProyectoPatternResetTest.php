<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProyectoPatternResetTest extends TestCase
{
    use LogErrorsTrait;

    private $projects = [];

    public function testAnnualPatternResetDisabled(): void
    {
        Tools::settingsSet('proyectos', 'patron', 'PR-{ANYO}-{NUM}');
        Tools::settingsSet('proyectos', 'longnumero', 1);
        Tools::settingsSet('proyectos', 'reiniciar_patron_anualmente', 0);

        // obtenemos el año anterior
        $prevYear = date('Y') - 1;

        // obtenemos el año actual
        $currentYear = date('Y');

        // Crear 3 proyectos en año anterior
        for ($i = 1; $i <= 3; $i++) {
            $p = new Proyecto();
            $p->descripcion = "Proyecto $i año anterior";
            $p->fecha = "$prevYear-01-01";
            $this->assertTrue($p->save(), "No se pudo guardar el proyecto $i del año anterior");
            $this->projects[] = $p;
            $this->assertEquals("PR-$prevYear-$i", $p->nombre, "El patrón del proyecto $i del año anterior es incorrecto");
        }

        // Crear 3 proyectos en año actual
        for ($i = 4; $i <= 6; $i++) {
            $p = new Proyecto();
            $p->descripcion = "Proyecto $i año actual";
            $p->fecha = "$currentYear-01-01";
            $this->assertTrue($p->save(), "No se pudo guardar el proyecto $i del año actual");
            $this->projects[] = $p;
            $this->assertEquals("PR-$currentYear-$i", $p->nombre, "El patrón del proyecto $i del año actual es incorrecto");
        }
    }

    public function testAnnualPatternResetEnabled(): void
    {
        Tools::settingsSet('proyectos', 'patron', 'PR-{ANYO}-{NUM}');
        Tools::settingsSet('proyectos', 'longnumero', 1);
        Tools::settingsSet('proyectos', 'reiniciar_patron_anualmente', 1);

        // obtenemos el año anterior
        $prevYear = date('Y') - 1;

        // obtenemos el año actual
        $currentYear = date('Y');

        // Crear 3 proyectos para el año anterior
        for ($i = 1; $i <= 3; $i++) {
            $p = new Proyecto();
            $p->descripcion = "Proyecto $i año anterior (reset on)";
            $p->fecha = "$prevYear-01-01";
            $this->assertTrue($p->save(), "No se pudo guardar el proyecto $i del año anterior con reset");
            $this->projects[] = $p;
            $this->assertEquals("PR-$prevYear-$i", $p->nombre, "El patrón del proyecto $i del año anterior es incorrecto con reset");
        }

        // Crear 3 proyectos para el año actual
        for ($i = 1; $i <= 3; $i++) {
            $p = new Proyecto();
            $p->descripcion = "Proyecto $i año actual (reset on)";
            $p->fecha = "$currentYear-01-01";
            $this->assertTrue($p->save(), "No se pudo guardar el proyecto $i del año actual con reset");
            $this->projects[] = $p;
            $this->assertEquals("PR-$currentYear-$i", $p->nombre, "El patrón del proyecto $i del año actual debe reiniciarse a $i");
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->projects as $p) {
            $p->delete();
        }
        $this->logErrors();
    }
}
