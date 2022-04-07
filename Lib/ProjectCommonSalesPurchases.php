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

namespace FacturaScripts\Plugins\Proyectos\Lib;

trait ProjectCommonSalesPurchases
{
    private static function scriptAutocompleteProject($model): string
    {
        return '$("#findProjectInput").autocomplete({'
            . 'source: function (request, response) {'
            . '$.ajax({'
            . 'method: "POST",'
            . 'url: "' . $model->url() . '",'
            . 'data: {action: "autocomplete-project", term: request.term},'
            . 'dataType: "json",'
            . 'success: function (results) {'
            . 'let values = [];'
            . 'results.forEach(function (element) {'
            . 'if (element.key === null || element.key === element.value) {'
            . 'values.push(element);'
            . '} else {'
            . 'values.push({key: element.key, value: element.key + " | " + element.value});'
            . '}'
            . '});'
            . 'response(values);'
            . '},'
            . 'error: function (msg) {'
            . 'alert(msg.status + " " + msg.responseText);'
            . '}'
            . '});'
            . '},'
            . 'select: function (event, ui) {'
            . 'if (ui.item.key !== null) {'
            . 'const value = ui.item.value.split(" | ");'
            . '$("input[name=\"idproyecto\"]").val(value[0]);'
            . '$("#deleteProject").removeClass("d-none");'
            . '$("#searchProject").addClass("d-none");'
            . '}'
            . '}'
            . '});';
    }

    private static function styleCSS(): string
    {
        return '.ui-autocomplete {z-index:2147483647;}';
    }

    private static function scriptDeleteProject()
    {
        return '$("#deleteProject").click(function() {'
            . '$("input[name=\"idproyecto\"]").val("");'
            . '$("#findProjectInput").val("");'
            . '$("#deleteProject").addClass("d-none");'
            . '$("#searchProject").removeClass("d-none");'
            . '});';
    }
}