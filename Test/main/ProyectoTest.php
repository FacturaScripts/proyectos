<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2025-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Plugins\Proyectos\Model\UserProyecto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProyectoTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
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

    public function testOwnerMemberAndAdminSeePrivateProjects(): void
    {
        // usuario real de otra empresa, necesario para las claves ajenas de nick
        $user = new User();
        $user->nick = 'test_proy_' . mt_rand(1000, 9999);
        $user->setPassword('test9876');
        $this->assertTrue($user->save());
        $user->idempresa = 99999;

        // proyecto privado creado por el usuario
        $owned = $this->createProject('Proyecto privado propio', true);
        $owned->nick = $user->nick;
        $this->assertTrue($owned->save());
        $this->assertTrue($owned->userCanSee($user));
        $this->assertContains($owned->idproyecto, $this->visibleSqlIds($user));

        // proyecto privado en el que el usuario está asignado
        $assigned = $this->createProject('Proyecto privado asignado', true);
        $this->assertFalse($assigned->userCanSee($user));
        $userProject = new UserProyecto();
        $userProject->idproyecto = $assigned->idproyecto;
        $userProject->nick = $user->nick;
        $this->assertTrue($userProject->save());
        $this->assertTrue($assigned->userCanSee($user));
        $this->assertContains($assigned->idproyecto, $this->visibleSqlIds($user));

        // un administrador ve cualquier proyecto, aunque sea de otra empresa
        $admin = $this->memoryUser('test-admin', 99999);
        $admin->admin = true;
        $this->assertTrue($assigned->userCanSee($admin));
        $this->assertContains($assigned->idproyecto, $this->visibleSqlIds($admin));

        // eliminamos los proyectos y el usuario
        $this->assertTrue($owned->delete());
        $this->assertTrue($assigned->delete());
        $this->assertTrue($user->delete());
    }

    public function testUserDoesNotSeePrivateProjectsOfItsCompany(): void
    {
        $private = $this->createProject('Proyecto privado de la empresa', true);

        // un usuario de la misma empresa que no es creador ni está asignado no lo ve,
        // ni en la ficha ni en la consulta de «Mis tareas»
        $user = $this->memoryUser('test-no-asignado', $private->idempresa);
        $this->assertNotContains($private->idproyecto, $this->visibleSqlIds($user));
        $this->assertFalse($private->userCanSee($user));

        // eliminamos el proyecto
        $this->assertTrue($private->delete());
    }

    public function testUserWithCompanySeesOnlyItsCompany(): void
    {
        $project = $this->createProject('Proyecto público con empresa', false);

        // usuario de la misma empresa
        $user = $this->memoryUser('test-misma-empresa', $project->idempresa);
        $this->assertContains($project->idproyecto, $this->visibleIds($user));
        $this->assertContains($project->idproyecto, $this->visibleSqlIds($user));
        $this->assertTrue($project->userCanSee($user));

        // usuario de otra empresa
        $other = $this->memoryUser('test-otra-empresa', 99999);
        $this->assertNotContains($project->idproyecto, $this->visibleIds($other));
        $this->assertNotContains($project->idproyecto, $this->visibleSqlIds($other));
        $this->assertFalse($project->userCanSee($other));

        // eliminamos el proyecto
        $this->assertTrue($project->delete());
    }

    public function testUserWithoutCompanySeesPublicProjects(): void
    {
        $public = $this->createProject('Proyecto público sin empresa', false);
        $private = $this->createProject('Proyecto privado sin empresa', true);

        // usuario sin empresa asignada, como los creados con una sola empresa
        $user = $this->memoryUser('test-sin-empresa', null);

        // el listado de proyectos incluye el público y no el privado
        $ids = $this->visibleIds($user);
        $this->assertContains($public->idproyecto, $ids);
        $this->assertNotContains($private->idproyecto, $ids);

        // la consulta de proyectos visibles también incluye el público y no el privado
        $ids = $this->visibleSqlIds($user);
        $this->assertContains($public->idproyecto, $ids);
        $this->assertNotContains($private->idproyecto, $ids);

        // y la ficha del proyecto aplica la misma regla
        $this->assertTrue($public->userCanSee($user));
        $this->assertFalse($private->userCanSee($user));

        // eliminamos los proyectos
        $this->assertTrue($public->delete());
        $this->assertTrue($private->delete());
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
     * Crea un usuario en memoria, sin guardarlo, con el nick y la empresa indicados.
     *
     * @param string $nick Nick del usuario.
     * @param int|null $idempresa Empresa del usuario o null si no tiene.
     *
     * @return User
     */
    private function memoryUser(string $nick, ?int $idempresa): User
    {
        $user = new User();
        $user->nick = $nick;
        $user->idempresa = $idempresa;

        return $user;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
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

    /**
     * Devuelve los identificadores de los proyectos que devuelve Proyecto::visibleProjectsSql().
     *
     * @param User $user Usuario que consulta.
     *
     * @return int[]
     */
    private function visibleSqlIds(User $user): array
    {
        $db = new DataBase();
        $sql = Proyecto::visibleProjectsSql($user, $db);
        return array_map('intval', array_column($db->select($sql), 'idproyecto'));
    }
}
