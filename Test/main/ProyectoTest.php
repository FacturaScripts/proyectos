<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProyectoTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreateProject(): void
    {
        // creamos un proyecto nuevo
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto de prueba';
        $proyecto->descripcion = 'Este es un proyecto de prueba';
        $this->assertTrue($proyecto->save());

        // comprobamos que se ha creado correctamente
        $this->assertTrue($proyecto->exists());

        // comprobamos que tiene un estado por defecto y es editable
        $this->assertNotNull($proyecto->idestado);
        $this->assertTrue($proyecto->editable);

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());
    }

    public function testCloseProject(): void
    {
        // creamos un proyecto nuevo
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto de prueba';
        $proyecto->descripcion = 'Este es un proyecto de prueba';
        $this->assertTrue($proyecto->save());

        // cerramos el proyecto
        foreach ($proyecto->getAvailableStatus() as $status) {
            if (false === $status->editable) {
                $proyecto->idestado = $status->idestado;
                break;
            }
        }
        $this->assertTrue($proyecto->save());

        // comprobamos que el proyecto ya no es editable
        $this->assertFalse($proyecto->editable);

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
