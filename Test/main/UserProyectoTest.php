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
use FacturaScripts\Plugins\Proyectos\Model\UserProyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class UserProyectoTest extends TestCase
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

    public function testCreateUserProyecto(): void
    {
        // creamos un proyecto
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto para usuarios';
        $this->assertTrue($proyecto->save());

        // creamos un usuario
        $user = $this->getRandomUser();
        $this->assertTrue($user->save());

        // asignamos el usuario al proyecto
        $userProyecto = new UserProyecto();
        $userProyecto->idproyecto = $proyecto->idproyecto;
        $userProyecto->nick = $user->nick;
        $this->assertTrue($userProyecto->save());

        // comprobamos que se ha creado correctamente
        $this->assertTrue($userProyecto->exists());

        // eliminamos la asignación
        $this->assertTrue($userProyecto->delete());

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());

        // eliminamos el usuario
        $this->assertTrue($user->delete());
    }

    public function testCannotCreateWithoutProject(): void
    {
        // creamos la asignación sin proyecto
        $userProyecto = new UserProyecto();
        $userProyecto->nick = 'test';
        $this->assertFalse($userProyecto->save());

        // creamos la asignación sin proyecto pero con usuario
        $user = $this->getRandomUser();
        $this->assertTrue($user->save());
        $userProyecto->nick = $user->nick;
        $this->assertFalse($userProyecto->save());
    }

    public function testDeleteProjectDeletesUserProyecto(): void
    {
        // creamos un proyecto
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto para usuarios 2';
        $this->assertTrue($proyecto->save());

        // creamos un usuario
        $user = $this->getRandomUser();
        $this->assertTrue($user->save());

        // asignamos el usuario al proyecto
        $userProyecto = new UserProyecto();
        $userProyecto->idproyecto = $proyecto->idproyecto;
        $userProyecto->nick = $user->nick;
        $this->assertTrue($userProyecto->save());

        // comprobamos que la asignación existe
        $this->assertTrue($userProyecto->exists());

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());

        // comprobamos que la asignación ya no existe
        $this->assertFalse($userProyecto->exists());

        // eliminamos el usuario
        $this->assertTrue($user->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
