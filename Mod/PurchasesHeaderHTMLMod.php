<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Proyectos\Mod;

use FacturaScripts\Core\Contract\PurchasesModInterface;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

/**
 * Description of PurchasesHeaderHTMLMod
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class PurchasesHeaderHTMLMod implements PurchasesModInterface
{
    public function apply(PurchaseDocument &$model, array $formData): void
    {
    }

    public function applyBefore(PurchaseDocument &$model, array $formData): void
    {
        // aplicamos antes para asegurarnos de capturar el valor incluso en nuevas compras,
        // antes de seleccionar proveedor
        $model->idproyecto = isset($formData['idproyecto']) && $formData['idproyecto'] ? $formData['idproyecto'] : null;
    }

    public function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::add('js', $route . '/Dinamic/Assets/JS/AutocompleteProject.js');
    }

    public function newBtnFields(): array
    {
        return [];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['proyecto'];
    }

    public function renderField(PurchaseDocument $model, string $field): ?string
    {
        if ($field === 'proyecto') {
            return self::proyecto($model);
        }

        return null;
    }

    private static function proyecto(PurchaseDocument $model): string
    {
        $value = '';
        $project = new Proyecto();
        if ($model->idproyecto && $project->load($model->idproyecto)) {
            $value = $project->idproyecto . ' | ' . $project->nombre;
        }

        $html = '<div class="col-sm-12">'
            . '<a href="' . $project->url() . '">' . Tools::trans('project') . '</a>'
            . '<div class="input-group">'
            . '';

        if ($model->editable && $model->idproyecto) {
            $html .= '<button type="button" id="deleteProject" class="btn btn-warning">'
                . '<i class="fa-solid fa-times" aria-hidden="true"></i>'
                . '</button>';
        } else {
            $html .= '<span id="searchProject" class="input-group-text">'
                . '<i class="fa-solid fa-search fa-fw"></i>'
                . '</span>';
        }

        $disabled = $model->editable ? '' : 'disabled';
        $html .= ''
            . '<input type="hidden" name="idproyecto" value="' . $model->idproyecto . '">'
            . '<input type="text" id="findProjectInput" class="form-control" value="' . $value . '" ' . $disabled . '/>'
            . '</div>'
            . '</div>';
        return $html;
    }
}
