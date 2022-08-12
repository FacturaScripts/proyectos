<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

/**
 * Description of SalesHeaderHTMLMod
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class SalesHeaderHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData, User $user)
    {
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {
        $model->idproyecto = isset($formData['idproyecto']) && $formData['idproyecto'] ? $formData['idproyecto'] : null;
    }

    public function assets(): void
    {
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/AutocompleteProject.js');
    }

    public function newFields(): array
    {
        return ['proyecto'];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field === 'proyecto') {
            return self::proyecto($i18n, $model);
        }
        return null;
    }

    private static function proyecto(Translator $i18n, SalesDocument $model): string
    {
        $value = '';
        $project = new Proyecto();
        if ($model->idproyecto && $project->loadFromCode($model->idproyecto)) {
            $value = $project->idproyecto . ' | ' . $project->nombre;
        }

        $html = '<div class="col-sm-6">'
            . '<a href="' . $project->url() . '">' . $i18n->trans('project') . '</a>'
            . '<div class="input-group">'
            . '<div class="input-group-prepend">';

        if ($model->editable && $model->idproyecto) {
            $html .= '<button type="button" id="deleteProject" class="btn btn-warning">'
                . '<i class="fas fa-times" aria-hidden="true"></i>'
                . '</button>';
        } else {
            $html .= '<span id="searchProject" class="input-group-text">'
                . '<i class="fas fa-search fa-fw"></i>'
                . '</span>';
        }

        $disabled = $model->editable ? '' : 'disabled';
        $html .= '</div>'
            . '<input type="hidden" name="idproyecto" value="' . $model->idproyecto . '">'
            . '<input type="text" id="findProjectInput" class="form-control" value="' . $value . '" ' . $disabled . '/>'
            . '</div>'
            . '</div>';
        return $html;
    }
}