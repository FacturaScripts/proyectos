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

use FacturaScripts\Plugins\Proyectos\Model\FaseTarea;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Plugins\Proyectos\Model\TareaProyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class FaseTareaTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreateFaseTarea(): void
    {
        // creamos una fase nueva
        $fase = new FaseTarea();
        $fase->nombre = 'Fase de prueba';
        $this->assertTrue($fase->save());

        // comprobamos que se ha creado correctamente
        $this->assertTrue($fase->exists());

        // eliminamos la fase
        $this->assertTrue($fase->delete());
    }

    public function testDeleteFaseNoEliminaTarea(): void
    {
        // creamos un proyecto
        $proyecto = new Proyecto();
        $proyecto->nombre = 'Proyecto para Tarea con Fase';
        $this->assertTrue($proyecto->save());

        // creamos una fase nueva
        $fase = new FaseTarea();
        $fase->nombre = 'Fase para borrar';
        $fase->idproyecto = $proyecto->idproyecto;
        $this->assertTrue($fase->save());

        // creamos una tarea y le asignamos la fase
        $tarea = new TareaProyecto();
        $tarea->idproyecto = $proyecto->idproyecto;
        $tarea->nombre = 'Tarea que no debe borrarse';
        $tarea->idfase = $fase->idfase;
        $tarea->cantidad = 1;
        $this->assertTrue($tarea->save());

        // eliminamos la fase (no debe poderse eliminar, primero eliminar la tarea)
        $this->assertFalse($fase->delete());

        // eliminamos la tarea
        $this->assertTrue($tarea->delete());

        // comprobamos que la fase existe
        $this->assertTrue($fase->exists());

        // eliminamos la fase y el proyecto
        $this->assertTrue($fase->delete());
        $this->assertTrue($proyecto->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
