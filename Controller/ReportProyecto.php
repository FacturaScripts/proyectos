<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2020-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Informe de proyectos: abiertos totales, del último mes, del último año y desglose por estado.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class ReportProyecto extends Controller
{
    /** @var array */
    public $projectsByClient = [];

    /** @var array */
    public $projectsByMonth = [];

    /** @var array */
    public $projectsByNick = [];

    /** @var array */
    public $projectsByStatus = [];

    /** @var array */
    public $projectsByYear = [];

    /** @var int */
    public $openProjects;

    /** @var int */
    public $openProjectsLastMonth;

    /** @var int */
    public $openProjectsLastYear;

    /** @var int */
    public $totalProjects;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'projects';
        $data['icon'] = 'fa-brands fa-stack-overflow';
        return $data;
    }

    public function run(): void
    {
        parent::run();

        $this->loadTotalProjects();
        $this->loadOpenProjects();
        $this->loadOpenProjectsLastMonth();
        $this->loadOpenProjectsLastYear();
        $this->loadProjectsByStatus();
        $this->loadProjectsByMonth();
        $this->loadProjectsByYear();
        $this->loadProjectsByNick();
        $this->loadProjectsByClient();

        $this->view('ReportProyecto.html.twig');
    }

    protected function loadTotalProjects(): void
    {
        $sql = 'SELECT COUNT(*) as total FROM proyectos';
        $result = $this->db()->select($sql);
        $this->totalProjects = (int)($result[0]['total'] ?? 0);
    }

    protected function loadProjectsByNick(): void
    {
        $sql = 'SELECT nick, COUNT(*) as total'
            . ' FROM proyectos'
            . ' GROUP BY nick'
            . ' ORDER BY total DESC';
        $this->projectsByNick = $this->db()->select($sql);
    }

    protected function loadProjectsByClient(): void
    {
        $sql = 'SELECT p.codcliente, c.nombre, COUNT(p.idproyecto) as total'
            . ' FROM proyectos p'
            . ' LEFT JOIN clientes c ON c.codcliente = p.codcliente'
            . ' GROUP BY p.codcliente, c.nombre'
            . ' ORDER BY total DESC';
        $this->projectsByClient = $this->db()->select($sql);
    }

    protected function loadOpenProjects(): void
    {
        $sql = 'SELECT COUNT(*) as total FROM proyectos'
            . ' WHERE editable = ' . $this->db()->var2str(true);
        $result = $this->db()->select($sql);
        $this->openProjects = (int)($result[0]['total'] ?? 0);
    }

    protected function loadOpenProjectsLastMonth(): void
    {
        $since = date('Y-m-d', strtotime('-1 month'));
        $sql = "SELECT COUNT(*) as total FROM proyectos WHERE fecha >= '" . $since . "'";
        $result = $this->db()->select($sql);
        $this->openProjectsLastMonth = (int)($result[0]['total'] ?? 0);
    }

    protected function loadOpenProjectsLastYear(): void
    {
        $since = date('Y-m-d', strtotime('-1 year'));
        $sql = "SELECT COUNT(*) as total FROM proyectos WHERE fecha >= '" . $since . "'";
        $result = $this->db()->select($sql);
        $this->openProjectsLastYear = (int)($result[0]['total'] ?? 0);
    }

    protected function loadProjectsByStatus(): void
    {
        $sql = 'SELECT e.idestado, e.nombre, e.color, e.editable, COUNT(p.idproyecto) as total,'
            . ' COALESCE(SUM(p.totalventas), 0) as totalventas'
            . ' FROM proyectos_estados e'
            . ' LEFT JOIN proyectos p ON p.idestado = e.idestado'
            . ' GROUP BY e.idestado, e.nombre, e.color, e.editable'
            . ' ORDER BY total DESC';
        $this->projectsByStatus = $this->db()->select($sql);
    }

    protected function loadProjectsByMonth(): void
    {
        $now = new \DateTime();
        for ($i = 11; $i >= 0; $i--) {
            $date = clone $now;
            $date->modify("-$i months");
            $this->projectsByMonth[$date->format('Y-m')] = 0;
        }

        $since = (clone $now)->modify('-11 months')->format('Y-m-01');
        $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') as periodo, COUNT(*) as total"
            . ' FROM proyectos'
            . " WHERE fecha >= '" . $since . "'"
            . " GROUP BY DATE_FORMAT(fecha, '%Y-%m')"
            . ' ORDER BY periodo ASC';
        foreach ($this->db()->select($sql) as $row) {
            if (isset($this->projectsByMonth[$row['periodo']])) {
                $this->projectsByMonth[$row['periodo']] = (int)$row['total'];
            }
        }
    }

    protected function loadProjectsByYear(): void
    {
        $sql = 'SELECT YEAR(fecha) as periodo, COUNT(*) as total'
            . ' FROM proyectos'
            . ' GROUP BY YEAR(fecha)'
            . ' ORDER BY periodo ASC';
        foreach ($this->db()->select($sql) as $row) {
            $this->projectsByYear[(string)$row['periodo']] = (int)$row['total'];
        }
    }
}