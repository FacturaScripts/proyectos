<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Plugins\Proyectos\Model\TareaProyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class TareaProyectoTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreateTareaProyecto(): void
    {
        // creamos un proyecto
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto para tareas';
        $this->assertTrue($proyecto->save());

        // creamos una tarea nueva
        $tarea = new TareaProyecto();
        $tarea->idproyecto = $proyecto->idproyecto;
        $tarea->cantidad = 1;
        $tarea->nombre = 'Tarea de prueba';
        $this->assertTrue($tarea->save());

        // comprobamos que se ha creado correctamente
        $this->assertTrue($tarea->exists());

        // eliminamos la tarea
        $this->assertTrue($tarea->delete());

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());
    }

    public function testCannotCreateWithoutProject(): void
    {
        // creamos una tarea sin proyecto
        $tarea = new TareaProyecto();
        $tarea->nombre = 'Esta tarea no debería guardarse';
        $tarea->cantidad = 1;
        $this->assertFalse($tarea->save());
    }

    public function testDeleteProjectDeletesTareas(): void
    {
        // creamos un proyecto
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto para tareas 2';
        $this->assertTrue($proyecto->save());

        // creamos una tarea nueva
        $tarea = new TareaProyecto();
        $tarea->idproyecto = $proyecto->idproyecto;
        $tarea->nombre = 'Tarea para probar borrado en cascada';
        $tarea->cantidad = 1;
        $this->assertTrue($tarea->save());

        // comprobamos que la tarea existe
        $this->assertTrue($tarea->exists());

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());

        // comprobamos que la tarea ya no existe
        $this->assertFalse($tarea->exists());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
