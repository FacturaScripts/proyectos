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

namespace FacturaScripts\Plugins\Proyectos\Controller;

use FacturaScripts\Core\Template\Controller;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\EstadoProyecto;
use FacturaScripts\Dinamic\Model\FaseTarea;
use FacturaScripts\Dinamic\Model\Proyecto;
use FacturaScripts\Dinamic\Model\TareaProyecto;

class KanbanProyectos extends Controller
{
    /** @var FaseTarea[] */
    public array $phases = [];

    /** @var Proyecto */
    public $project;

    /** @var EstadoProyecto|null */
    public $projectStatus;

    /** @var Proyecto[] */
    public array $projects = [];

    /** @var array<int, TareaProyecto[]> */
    public array $tasksByPhase = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'kanban';
        $data['icon'] = 'fa-brands fa-trello';
        return $data;
    }

    public function run(): void
    {
        parent::run();

        $action = $this->request()->inputOrQuery('action', '');
        if ($action === 'move-task') {
            $this->moveTaskAction();
            return;
        }

        $this->loadProjects();
        $this->loadSelectedProject();
        $this->loadPhasesAndTasks();

        $this->view('KanbanProyectos.html.twig');
    }

    private function moveTaskAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->response()->json(['ok' => false, 'error' => 'not-allowed']);
            return;
        }

        $idtarea = (int) $this->request()->input('idtarea', '0');
        $idfase = (int) $this->request()->input('idfase', '0');

        $task = new TareaProyecto();
        if ($idtarea > 0 && $task->load($idtarea)) {
            $task->idfase = $idfase;
            $task->save();
            $this->response()->json(['ok' => true]);
            return;
        }

        $this->response()->json(['ok' => false]);
    }

    private function loadProjects(): void
    {
        $proyecto = new Proyecto();
        $allProjects = $proyecto->all([], ['nombre' => 'ASC'], 0, 0);

        foreach ($allProjects as $project) {
            if ($project->userCanSee($this->user)) {
                $this->projects[] = $project;
            }
        }
    }

    private function loadSelectedProject(): void
    {
        $idproyecto = (int) $this->request()->query('idproyecto', '0');

        $this->project = new Proyecto();
        if ($idproyecto > 0) {
            $this->project->load($idproyecto);
        } elseif (!empty($this->projects)) {
            $this->project = $this->projects[0];
        }

        if (!empty($this->project->idestado)) {
            $this->projectStatus = $this->project->getStatus();
        }
    }

    private function loadPhasesAndTasks(): void
    {
        $faseTarea = new FaseTarea();
        $this->phases = $faseTarea->all([], [], 0, 0);

        foreach ($this->phases as $phase) {
            $this->tasksByPhase[$phase->idfase] = [];
        }

        if (empty($this->project->idproyecto)) {
            return;
        }

        $where = [Where::eq('idproyecto', $this->project->idproyecto)];
        $tasks = TareaProyecto::all($where, ['nombre' => 'ASC'], 0, 0);
        foreach ($tasks as $task) {
            if (isset($this->tasksByPhase[$task->idfase])) {
                $this->tasksByPhase[$task->idfase][] = $task;
            }
        }
    }
}
