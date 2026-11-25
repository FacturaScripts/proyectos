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

use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ProjectDocumentLinker;
use FacturaScripts\Dinamic\Model\Proyecto;

/**
 * Endpoint de la API para vincular o desvincular de un proyecto un documento de compra o
 * venta o un asiento contable: POST /api/3/vincularProyecto.
 *
 * Hace lo mismo que los botones de vincular y desvincular de la ficha del proyecto, por lo
 * que también admite documentos no editables y asientos de ejercicios cerrados, que la API
 * genérica de modelos rechaza.
 *
 * Parámetros (POST o PUT):
 *   - modelo: PresupuestoCliente, PedidoCliente, AlbaranCliente, FacturaCliente,
 *     PresupuestoProveedor, PedidoProveedor, AlbaranProveedor, FacturaProveedor o Asiento.
 *   - id: clave primaria del documento o del asiento.
 *   - idproyecto: proyecto al que vincularlo. Vacío o «null» para desvincularlo.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ApiVincularProyecto extends ApiController
{
    /**
     * Valida la petición, vincula o desvincula el registro y devuelve el resultado.
     */
    protected function runResource(): void
    {
        if (false === in_array($this->request->method(), ['POST', 'PUT'], true)) {
            $this->sendError('method-not-allowed', Response::HTTP_METHOD_NOT_ALLOWED);
            return;
        }

        // comprobamos el modelo
        $modelName = (string)$this->request->request->get('modelo', '');
        if (false === ProjectDocumentLinker::isValidModel($modelName)) {
            $this->sendError('invalid-model', Response::HTTP_BAD_REQUEST, ['allowed' => ProjectDocumentLinker::MODELS]);
            return;
        }

        // cargamos el documento o asiento
        $code = $this->request->request->get('id', '');
        if (empty($code)) {
            $this->sendError('missing-id-parameter', Response::HTTP_BAD_REQUEST);
            return;
        }

        $record = ProjectDocumentLinker::loadRecord($modelName, $code);
        if (null === $record) {
            $this->sendError(Tools::trans('record-not-found'), Response::HTTP_NOT_FOUND);
            return;
        }

        // comprobamos el proyecto: vacío o «null» desvincula
        $idproyecto = $this->request->request->get('idproyecto', '');
        $idproyecto = in_array($idproyecto, ['', 'null', '0', 0, null], true) ? null : (int)$idproyecto;
        if (null !== $idproyecto && false === (new Proyecto())->load($idproyecto)) {
            $this->sendError('project-not-found', Response::HTTP_NOT_FOUND);
            return;
        }

        $previous = empty($record->idproyecto) ? null : (int)$record->idproyecto;
        if (false === ProjectDocumentLinker::link($record, $idproyecto)) {
            $this->sendError(Tools::trans('record-save-error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json([
                'ok' => Tools::trans('record-updated-correctly'),
                'data' => [
                    'modelo' => $modelName,
                    'id' => $record->id(),
                    'idproyecto' => $idproyecto,
                    'idproyecto_anterior' => $previous,
                ],
            ]);
    }

    /**
     * Envía una respuesta de error en el formato de los endpoints del core.
     *
     * @param string $message  mensaje o clave del error.
     * @param int    $httpCode código HTTP de la respuesta.
     * @param array  $extra    datos adicionales para la respuesta.
     */
    private function sendError(string $message, int $httpCode, array $extra = []): void
    {
        $this->response
            ->setHttpCode($httpCode)
            ->json(array_merge(['status' => 'error', 'message' => $message], $extra));
    }
}
