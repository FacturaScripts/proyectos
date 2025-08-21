<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Plugins\Proyectos\Model\EstadoProyecto;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class EstadoProyectoTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreateEstadoProyecto(): void
    {
        // creamos un estado nuevo
        $estado = new EstadoProyecto();
        $estado->nombre = 'Estado de prueba';
        $estado->color = '#FF0000';
        $this->assertTrue($estado->save());

        // comprobamos que se ha creado correctamente
        $this->assertTrue($estado->exists());

        // eliminamos el estado
        $this->assertTrue($estado->delete());
    }

    public function testAssignEstadoToProyecto(): void
    {
        // creamos un estado nuevo
        $estado = new EstadoProyecto();
        $estado->nombre = 'Estado de prueba 2';
        $estado->color = '#00FF00';
        $this->assertTrue($estado->save());

        // creamos un proyecto nuevo
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto de prueba';
        $proyecto->descripcion = 'Este es un proyecto de prueba';

        // asignamos el estado al proyecto
        $proyecto->idestado = $estado->idestado;
        $this->assertTrue($proyecto->save());

        // comprobamos que se ha asignado correctamente
        $this->assertEquals($estado->idestado, $proyecto->idestado);

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());

        // eliminamos el estado
        $this->assertTrue($estado->delete());
    }

    public function testDeleteEstadoProyecto(): void
    {
        // creamos un estado nuevo
        $estado = new EstadoProyecto();
        $estado->nombre = 'Estado de prueba 3';
        $estado->color = '#0000FF';
        $this->assertTrue($estado->save());

        // creamos un proyecto nuevo
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto de prueba 2';
        $proyecto->descripcion = 'Este es un proyecto de prueba 2';
        $proyecto->idestado = $estado->idestado;
        $this->assertTrue($proyecto->save());

        // intentamos eliminar el estado (no debe poderse eliminar)
        $this->assertFalse($estado->delete());

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());

        // eliminamos el estado
        $this->assertTrue($estado->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}