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

namespace FacturaScripts\Plugins\Proyectos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

/**
 * Extensión del controlador SendMail que añade al email el enlace público del proyecto para portal cliente.
 *
 * Si el ajuste show_public_share_mail_body está activo y se está enviando un proyecto,
 * añade al cuerpo del mensaje la url pública con el código de compartición para que el cliente
 * pueda verlo sin identificarse.
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class SendMail
{
    /**
     * Añade al cuerpo del email el texto y el enlace público del proyecto que se envía.
     *
     * @return Closure
     */
    public function loadDataDefault(): Closure
    {
        return function ($model) {
            if (false === ((bool)Tools::settings('portalcliente', 'show_public_share_mail_body', false))
                || $model->modelClassName() !== 'Proyecto') {
                return;
            }

            $this->newMail->body(
                $this->newMail->text
                . "\n\n"
                . Tools::trans('public-share-project-mail-body')
                . "\n"
                . Tools::siteUrl() . '/' . $model->url('public-share')
            );
        };
    }
}
