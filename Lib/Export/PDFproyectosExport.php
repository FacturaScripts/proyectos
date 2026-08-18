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

use FacturaScripts\Core\Lib\Export\PDFExport;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Proyecto;

/**
 * Genera el PDF de un proyecto con sus datos, el cliente y las tareas, en lugar del
 * genérico (que solo pinta las columnas de la vista, vacío si no las hay, como pasa en
 * la ficha del portal del cliente).
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class PDFproyectosExport extends PDFExport
{
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $this->newPage();
        $this->insertHeader($model->idempresa);
        $this->pdf->ezText("\n" . $title . ': ' . $model->nombre . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $this->insertParallelTable($this->projectData($model), '', $this->tableOptions());
        $this->pdf->ezText('');

        if (!empty($model->descripcion)) {
            $this->printTextSection('description', $model->descripcion);
        }

        $tasksData = $this->tasksData($model);
        if ($tasksData) {
            $this->printTableSection('tasks', $tasksData);
        }

        $footer = Tools::settings('proyectos', 'print_pdf_footer_text', '');
        $this->printTextSection('', $footer, false);

        return false;
    }

    protected function printTableSection(string $title, array $data): void
    {
        $this->pdf->ezText("\n" . $this->i18n->trans($title) . "\n", self::FONT_SIZE + 4);
        $this->newLine();
        $this->pdf->ezTable($data, '', '', $this->tableOptions(1));
        $this->pdf->ezText('');
    }

    protected function printTextSection(string $title, ?string $text, bool $addLine = true): void
    {
        if (empty($text)) {
            return;
        }

        if ($title) {
            $this->pdf->ezText("\n" . $this->i18n->trans($title) . "\n", self::FONT_SIZE + 4);
        }
        if ($addLine) {
            $this->newLine();
        }
        $this->pdf->ezText(nl2br($text) . "\n", self::FONT_SIZE + 2);
    }

    protected function projectData(Proyecto $model): array
    {
        $customer = $model->getCustomer();

        return [
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $this->i18n->trans('start-date'), 'value' => $model->fechainicio],
            ['key' => $this->i18n->trans('end-date'), 'value' => $model->fechafin],
            ['key' => $this->i18n->trans('status'), 'value' => $model->getStatus()->nombre],
            ['key' => $this->i18n->trans('customer'), 'value' => Tools::fixHtml($customer->nombre)],
        ];
    }

    protected function tableOptions($headings = 0): array
    {
        return [
            'width' => $this->tableWidth,
            'showHeadings' => $headings,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => []
        ];
    }

    protected function tasksData(Proyecto &$model): array
    {
        $result = [];
        foreach ($model->getTasks() as $task) {
            $result[] = [
                $this->i18n->trans('name') => $task->nombre,
                $this->i18n->trans('phase') => $task->getPhase()->nombre,
                $this->i18n->trans('start-date') => $task->fechainicio,
                $this->i18n->trans('end-date') => $task->fechafin,
            ];
        }
        return $result;
    }
}
