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

use FacturaScripts\Plugins\Proyectos\Model\NotaProyecto;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class NotaProyectoTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreateNotaProyecto(): void
    {
        // creamos un proyecto
        $proyecto = new Proyecto();
        $proyecto->clear();
        $proyecto->nombre = 'Proyecto para notas';
        $proyecto->descripcion = 'Este es un proyecto de prueba';
        $this->assertTrue($proyecto->save());

        // crear un nuevo usuario
        $user = $this->getRandomUser();
        $this->assertTrue($user->save());

        // creamos una nota nueva
        $nota = new NotaProyecto();
        $nota->idproyecto = $proyecto->idproyecto;
        $nota->nick = $user->nick;
        $nota->descripcion = 'Esta es una nota de prueba';
        $this->assertTrue($nota->save());

        // comprobamos que se ha creado correctamente
        $this->assertTrue($nota->exists());

        // eliminamos la nota
        $this->assertTrue($nota->delete());

        // eliminamos el usuario
        $this->assertTrue($user->delete());

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());
    }

    public function testCannotCreateWithoutProject(): void
    {
        // creamos una nota sin proyecto
        $nota = new NotaProyecto();
        $nota->descripcion = 'Esta nota no debería guardarse';
        $this->assertFalse($nota->save());

        // creamos nots sin proyecto pero con usuario
        $user = $this->getRandomUser();
        $this->assertTrue($user->save());
        $nota->nick = $user->nick;
        $this->assertFalse($nota->save());
    }

    public function testDeleteProjectDeletesNotas(): void
    {
        // creamos un proyecto
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto para notas 2';
        $proyecto->descripcion = 'Este es un proyecto de prueba';
        $this->assertTrue($proyecto->save());

        // crear un nuevo usuario
        $user = $this->getRandomUser();
        $this->assertTrue($user->save());

        // creamos una nota nueva
        $nota = new NotaProyecto();
        $nota->idproyecto = $proyecto->idproyecto;
        $nota->descripcion = 'Nota para probar borrado en cascada';
        $nota->nick = $user->nick;
        $this->assertTrue($nota->save());

        // comprobamos que la nota existe
        $this->assertTrue($nota->exists());

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());

        // comprobamos que la nota ya no existe
        $this->assertFalse($nota->exists());

        // eliminamos el usuario
        $this->assertTrue($user->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}