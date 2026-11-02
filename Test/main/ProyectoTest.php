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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\User;
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

    public function testCreateProjectDirectlyClosed(): void
    {
        // buscamos un estado no editable
        $proyecto = new Proyecto();
        $closedStatus = null;
        foreach ($proyecto->getAvailableStatus() as $status) {
            if (false === $status->editable) {
                $closedStatus = $status;
                break;
            }
        }
        $this->assertNotNull($closedStatus, 'no-closed-status-found');

        // creamos el proyecto ya con ese estado desde el alta (no es un update)
        $proyecto->nombre = 'Proyecto de prueba';
        $proyecto->descripcion = 'Este es un proyecto de prueba';
        $proyecto->idestado = $closedStatus->idestado;
        $this->assertTrue($proyecto->save());

        // comprobamos que editable se ha sincronizado también en el alta
        $this->assertFalse($proyecto->editable);

        // eliminamos el proyecto
        $this->assertTrue($proyecto->delete());
    }

    public function testUserWithoutCompanySeesPublicProjects(): void
    {
        $public = $this->createProject('Proyecto público sin empresa', false);
        $private = $this->createProject('Proyecto privado sin empresa', true);

        // usuario sin empresa asignada, como los creados con una sola empresa
        $user = new User();
        $user->idempresa = null;

        // el listado de proyectos incluye el público y no el privado
        $ids = $this->visibleIds($user);
        $this->assertContains($public->idproyecto, $ids);
        $this->assertNotContains($private->idproyecto, $ids);

        // la consulta de proyectos de la empresa también incluye los públicos
        $sql = Proyecto::companyProjectsSql($user, new DataBase());
        $ids = array_map('intval', array_column((new DataBase())->select($sql), 'idproyecto'));
        $this->assertContains($public->idproyecto, $ids);
        $this->assertNotContains($private->idproyecto, $ids);

        // eliminamos los proyectos
        $this->assertTrue($public->delete());
        $this->assertTrue($private->delete());
    }

    public function testUserWithCompanySeesOnlyItsCompany(): void
    {
        $project = $this->createProject('Proyecto público con empresa', false);

        // usuario de la misma empresa
        $user = new User();
        $user->idempresa = $project->idempresa;
        $this->assertContains($project->idproyecto, $this->visibleIds($user));

        $sql = Proyecto::companyProjectsSql($user, new DataBase());
        $ids = array_map('intval', array_column((new DataBase())->select($sql), 'idproyecto'));
        $this->assertContains($project->idproyecto, $ids);

        // usuario de otra empresa
        $other = new User();
        $other->idempresa = 99999;
        $this->assertNotContains($project->idproyecto, $this->visibleIds($other));

        $sql = Proyecto::companyProjectsSql($other, new DataBase());
        $ids = array_map('intval', array_column((new DataBase())->select($sql), 'idproyecto'));
        $this->assertNotContains($project->idproyecto, $ids);

        // eliminamos el proyecto
        $this->assertTrue($project->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    /**
     * Crea y guarda un proyecto.
     *
     * @param string $name Nombre del proyecto.
     * @param bool $private Indica si el proyecto es privado.
     *
     * @return Proyecto
     */
    private function createProject(string $name, bool $private): Proyecto
    {
        $project = new Proyecto();
        $project->nombre = $name;
        $project->privado = $private;
        $this->assertTrue($project->save());

        return $project;
    }

    /**
     * Devuelve los identificadores de proyecto públicos que lista el usuario.
     *
     * @param User $user Usuario que consulta.
     *
     * @return array
     */
    private function visibleIds(User $user): array
    {
        $ids = [];
        foreach (Proyecto::all(Proyecto::publicWhere($user), [], 0, 0) as $project) {
            $ids[] = $project->idproyecto;
        }

        return $ids;
    }
}
