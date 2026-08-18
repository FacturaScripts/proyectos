<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2026 Daniel Fernández Giménez <contacto@danielfg.es>
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

namespace FacturaScripts\Plugins\Proyectos\Lib\Export;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Proyecto;
use FacturaScripts\Plugins\PlantillasPDF\Lib\Export\PDFExport;

/**
 * Genera el PDF de un proyecto usando la plantilla configurada en PlantillasPDF, con sus
 * datos, cliente, descripción y tareas.
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class PlantillasPDFproyectosExport extends PDFExport
{
    /**
     * @param Proyecto $model
     * @param array $columns
     * @param string $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $this->setFileName($title);
        if (isset($model->idempresa)) {
            $this->template->setEmpresa($model->idempresa);
        }
        $this->template->setHeaderTitle($title);

        $this->template->initMpdf();
        $this->template->initHtml();

        $this->projectData($model, $columns);
        $this->descriptionData($model);
        $this->tasksData($model);
        $this->footerData();
        return false;
    }

    protected function descriptionData(Proyecto $model): void
    {
        if (empty($model->descripcion)) {
            return;
        }

        $headers = [Tools::trans('description')];
        $rows = [[nl2br($model->descripcion)]];
        $this->addTablePage($headers, $rows, [], '');
    }

    protected function footerData(): void
    {
        $this->template->writeHTML(nl2br(Tools::settings('proyectos', 'print_pdf_footer_text', '')));
    }

    protected function projectData(Proyecto $model, array $columns): void
    {
        $excludeFields = ['descripcion'];

        $dataModel = $this->getModelColumnsData($model, $columns);
        foreach ($excludeFields as $field) {
            if (isset($dataModel[$field])) {
                unset($dataModel[$field]);
            }
        }

        $customer = $model->getCustomer();
        $dataModel['customer'] = [
            'title' => Tools::trans('customer'),
            'value' => $customer->nombre,
        ];

        $this->template->addDualColumnTable($dataModel);
    }

    protected function tasksData(Proyecto $model): void
    {
        $headers = [
            Tools::trans('name'),
            Tools::trans('phase'),
            Tools::trans('start-date'),
            Tools::trans('end-date'),
        ];

        $rows = [];
        foreach ($model->getTasks() as $task) {
            $rows[] = [
                $task->nombre,
                $task->getPhase()->nombre,
                $task->fechainicio,
                $task->fechafin,
            ];
        }

        if (empty($rows)) {
            return;
        }

        $this->addTablePage($headers, $rows, [], Tools::trans('tasks'));
    }
}
