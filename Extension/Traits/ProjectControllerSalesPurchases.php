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

namespace FacturaScripts\Plugins\Proyectos\Extension\Traits;

use Closure;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\Proyecto;

trait ProjectControllerSalesPurchases
{
    public function autocompleteProjectAction(): Closure
    {
        return function ($query) {
            $list = [];
            $project = new Proyecto();
            foreach ($project->codeModelSearch($query, 'idproyecto') as $value) {
                $list[] = [
                    'key' => ToolBox::utils()->fixHtml($value->code),
                    'value' => ToolBox::utils()->fixHtml($value->description)
                ];
            }

            if (empty($list)) {
                $list[] = ['key' => null, 'value' => ToolBox::i18n()->trans('no-data')];
            }

            return $list;
        };
    }

    public function execPreviousAction(): Closure
    {
        return function ($action) {
            if ($action === 'autocomplete-project') {
                $this->setTemplate(false);
                $query = (string)$this->request->get('term', '');
                $this->response->setContent(json_encode($this->autocompleteProjectAction($query)));
                return false;
            }
        };
    }
}