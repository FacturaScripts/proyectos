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

namespace FacturaScripts\Plugins\Proyectos\Controller\Informes;

use FacturaScripts\Core\Template\Controller;
use FacturaScripts\Core\Tools;

/**
 * Informe de tareas de proyectos. Solo se despliega si el plugin Informes está activado.
 */
class ReportTareaProyecto extends Controller
{
    /** Tareas con su fase, para poder filtrar por el tipo de fase. */
    const FROM = ' FROM tareas t INNER JOIN tareas_fases f ON f.idfase = t.idfase';

    /** Tareas abiertas: su fase no es completada (tipo 0) ni cancelada (tipo 1). */
    const OPEN = '(f.tipo IS NULL OR f.tipo NOT IN (0, 1))';

    public $overdueTasks = [];
    public $projectsWithMostOpenTasks = [];
    public $tasksByMonth = [];
    public $tasksByPhase = [];
    public $tasksByUser = [];
    public $tasksByYear = [];
    public $totals = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'project-tasks';
        $data['icon'] = 'fa-solid fa-list-check';
        return $data;
    }

    public function run(): void
    {
        parent::run();

        $this->loadTotals();
        $this->loadTasksByPhase();
        $this->loadTasksByUser();
        $this->loadProjectsWithMostOpenTasks();
        $this->loadOverdueTasks();
        $this->loadTasksByPeriod();

        $this->view('ReportTareaProyecto.html.twig');
    }

    protected function loadTotals(): void
    {
        $lastMonth = $this->db()->var2str(date('Y-m-d', strtotime('-1 month')));
        $lastYear = $this->db()->var2str(date('Y-m-d', strtotime('-1 year')));

        $this->totals = [
            'total' => $this->count(),
            'opened' => $this->count(self::OPEN),
            'last_month' => $this->count('t.fecha >= ' . $lastMonth),
            'last_year' => $this->count('t.fecha >= ' . $lastYear),
        ];

        // tiempo medio de resolución en días (AVG ignora las tareas sin fecha de completado)
        $sql = 'SELECT AVG(' . $this->resolutionDays() . ') AS media FROM tareas t';
        $this->totals['media'] = $this->db()->select($sql)[0]['media'] ?? null;
    }

    protected function loadTasksByPhase(): void
    {
        $sql = 'SELECT f.nombre, f.tipo, COUNT(t.idtarea) AS total'
            . ' FROM tareas_fases f'
            . ' LEFT JOIN tareas t ON t.idfase = f.idfase'
            . ' GROUP BY f.idfase, f.nombre, f.tipo'
            . ' ORDER BY total DESC';
        $this->tasksByPhase = $this->db()->select($sql);
    }

    protected function loadTasksByUser(): void
    {
        $sql = 'SELECT t.nick, COUNT(*) AS total,'
            . ' SUM(CASE WHEN ' . self::OPEN . ' THEN 1 ELSE 0 END) AS opened,'
            . ' SUM(CASE WHEN f.tipo = 0 THEN 1 ELSE 0 END) AS completed,'
            . ' SUM(CASE WHEN f.tipo = 1 THEN 1 ELSE 0 END) AS canceled,'
            . ' AVG(' . $this->resolutionDays() . ') AS media'
            . self::FROM
            . ' GROUP BY t.nick'
            . ' ORDER BY total DESC';
        $this->tasksByUser = $this->db()->select($sql);
    }

    protected function loadProjectsWithMostOpenTasks(): void
    {
        $sql = 'SELECT p.idproyecto, p.nombre, COUNT(*) AS total'
            . self::FROM
            . ' INNER JOIN proyectos p ON p.idproyecto = t.idproyecto'
            . ' WHERE ' . self::OPEN
            . ' GROUP BY p.idproyecto, p.nombre'
            . ' ORDER BY total DESC';
        $this->projectsWithMostOpenTasks = $this->db()->selectLimit($sql, 10);
    }

    protected function loadOverdueTasks(): void
    {
        $sql = 'SELECT t.idtarea, t.nombre, t.fechafin, p.nombre AS proyecto'
            . self::FROM
            . ' INNER JOIN proyectos p ON p.idproyecto = t.idproyecto'
            . ' WHERE ' . self::OPEN
            . ' AND t.fechafin < ' . $this->db()->var2str(Tools::date())
            . ' ORDER BY t.fechafin ASC';
        $this->overdueTasks = $this->db()->select($sql);
    }

    /**
     * Tareas creadas (fecha) y completadas (fechacompletado), por mes (últimos 12) y por año.
     */
    protected function loadTasksByPeriod(): void
    {
        // los últimos 12 meses empiezan a cero para que el gráfico muestre también los meses vacíos
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', mktime(0, 0, 0, (int)date('n') - $i, 1));
            $this->tasksByMonth[$month] = ['created' => 0, 'completed' => 0];
        }

        foreach (['created' => 'fecha', 'completed' => 'fechacompletado'] as $key => $column) {
            $year = 'EXTRACT(YEAR FROM ' . $column . ')';
            $month = 'EXTRACT(MONTH FROM ' . $column . ')';
            $sql = 'SELECT ' . $year . ' AS anyo, ' . $month . ' AS mes, COUNT(*) AS total'
                . ' FROM tareas'
                . ' WHERE ' . $column . ' IS NOT NULL'
                . ' GROUP BY ' . $year . ', ' . $month;

            foreach ($this->db()->select($sql) as $row) {
                $y = (int)$row['anyo'];
                $ym = sprintf('%04d-%02d', $y, $row['mes']);
                $total = (int)$row['total'];

                if (isset($this->tasksByMonth[$ym])) {
                    $this->tasksByMonth[$ym][$key] = $total;
                }

                if (false === isset($this->tasksByYear[$y])) {
                    $this->tasksByYear[$y] = ['created' => 0, 'completed' => 0];
                }
                $this->tasksByYear[$y][$key] += $total;
            }
        }

        ksort($this->tasksByYear);
    }

    /** Cuenta las tareas que cumplen la condición, o todas si no se indica ninguna. */
    private function count(string $where = ''): int
    {
        $sql = 'SELECT COUNT(*) AS total' . self::FROM;
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        return (int)($this->db()->select($sql)[0]['total'] ?? 0);
    }

    /** Expresión SQL con los días entre la creación de la tarea y su fecha de completado. */
    private function resolutionDays(): string
    {
        if (strtolower(Tools::config('db_type')) === 'postgresql') {
            return 'EXTRACT(EPOCH FROM (t.fechacompletado - CAST(t.fecha AS TIMESTAMP))) / 86400';
        }

        return 'TIMESTAMPDIFF(SECOND, t.fecha, t.fechacompletado) / 86400';
    }
}
