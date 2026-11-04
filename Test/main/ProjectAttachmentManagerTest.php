<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Plugins\Proyectos\Lib\ProjectAttachmentManager;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Comprueba que el número de adjuntos de los proyectos (numdocs) se mantiene al añadir
 * o quitar adjuntos y que ProjectAttachmentManager lo corrige cuando se desajusta.
 */
final class ProjectAttachmentManagerTest extends TestCase
{
    use LogErrorsTrait;

    public function testNumDocsFollowsAttachments(): void
    {
        // creamos un proyecto, que empieza sin adjuntos
        $project = new Proyecto();
        $project->nombre = 'Proyecto con adjuntos';
        $this->assertTrue($project->save(), 'can-not-save-project');
        $this->assertEquals(0, $project->numdocs);

        // al adjuntar dos archivos, el contador sube
        $file1 = $this->createFile();
        $relation1 = $this->attachFile($file1, $project->idproyecto);
        $this->assertEquals(1, $this->reloadNumDocs($project));

        $file2 = $this->createFile();
        $relation2 = $this->attachFile($file2, $project->idproyecto);
        $this->assertEquals(2, $this->reloadNumDocs($project));

        // al quitar uno, baja
        $this->assertTrue($relation1->delete(), 'can-not-delete-relation');
        $this->assertEquals(1, $this->reloadNumDocs($project));

        // los adjuntos de otros modelos no cuentan
        $other = new AttachedFileRelation();
        $other->idfile = $file1->idfile;
        $other->model = 'Producto';
        $other->modelid = $project->idproyecto;
        $other->modelcode = (string)$project->idproyecto;
        $this->assertTrue($other->save(), 'can-not-save-relation');
        $this->assertEquals(1, $this->reloadNumDocs($project));

        // si se desajusta, recalculate() lo corrige
        $this->assertTrue($project->update(['numdocs' => 5]));
        $this->assertTrue(ProjectAttachmentManager::recalculate($project->idproyecto));
        $this->assertEquals(1, $this->reloadNumDocs($project));

        // al borrar el archivo, la relación se borra en cascada sin pasar por el modelo:
        // el contador se queda igual hasta que se recalcula (lo hace el Cron)
        $this->assertTrue($file2->delete(), 'can-not-delete-file');
        $this->assertFalse($relation2->exists());
        $this->assertEquals(1, $this->reloadNumDocs($project));
        ProjectAttachmentManager::recalculateAll();
        $this->assertEquals(0, $this->reloadNumDocs($project));

        // un proyecto que no existe no se puede recalcular
        $this->assertFalse(ProjectAttachmentManager::recalculate(999999999));

        // limpiamos
        $this->assertTrue($other->delete());
        $this->assertTrue($file1->delete());
        $this->assertTrue($project->delete());
    }

    /**
     * Adjunta el archivo al proyecto como lo hace la pestaña de archivos de EditProyecto.
     */
    private function attachFile(AttachedFile $file, int $idproyecto): AttachedFileRelation
    {
        $relation = new AttachedFileRelation();
        $relation->idfile = $file->idfile;
        $relation->model = 'Proyecto';
        $relation->modelcode = (string)$idproyecto;
        $relation->modelid = $idproyecto;
        $this->assertTrue($relation->save(), 'can-not-save-relation');

        return $relation;
    }

    /**
     * Copia una imagen de prueba del core a MyFiles y la guarda como archivo adjunto.
     */
    private function createFile(): AttachedFile
    {
        $original = FS_FOLDER . '/Test/__files/product_image.jpg';
        $this->assertTrue(file_exists($original), 'file-not-found: ' . $original);

        $name = 'project_attachment_' . mt_rand(1, 999999) . '.jpg';
        $this->assertTrue(copy($original, FS_FOLDER . '/MyFiles/' . $name), 'file-not-copied');

        $file = new AttachedFile();
        $file->path = $name;
        $this->assertTrue($file->save(), 'can-not-save-file');

        return $file;
    }

    /**
     * Devuelve el número de adjuntos guardado en la base de datos para el proyecto.
     */
    private function reloadNumDocs(Proyecto $project): int
    {
        $this->assertTrue($project->reload());
        return (int)$project->numdocs;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
