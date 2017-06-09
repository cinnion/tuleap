<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Dashboard\Widget;

use ForgeConfig;
use HTTPRequest;
use Tuleap\Dashboard\Widget\Add\AddWidgetController;
use Tuleap\Widget\WidgetFactory;

class Router
{
    /**
     * @var PreferencesController
     */
    private $preferences_controller;
    /**
     * @var AddWidgetController
     */
    private $add_widget_controller;
    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    public function __construct(
        PreferencesController $preferences_controller,
        AddWidgetController $add_widget_controller,
        WidgetFactory $widget_factory
    ) {
        $this->preferences_controller = $preferences_controller;
        $this->add_widget_controller  = $add_widget_controller;
        $this->widget_factory         = $widget_factory;
    }

    public function route(HTTPRequest $request)
    {
        if (! $request->getCurrentUser()->isLoggedIn()) {
            return;
        }

        $action = $request->get('action');

        switch ($action) {
            case 'get-add-modal-content':
                $this->add_widget_controller->display($request);
                break;
            case 'get-edit-modal-content':
                $this->preferences_controller->display($request);
                break;
            case 'add-widget':
                $this->add_widget_controller->create($request);
                break;
            case 'edit-widget':
                $this->preferences_controller->update($request);
                break;
            case 'process-widget':
                $param  = $request->get('name');
                $name   = array_pop(array_keys($param));
                $widget = $this->widget_factory->getInstanceByWidgetName($name);

                $owner      = $request->get('owner');
                $owner_id   = (int) substr($owner, 1);
                $owner_type = substr($owner, 0, 1);
                $widget->process($owner_type, $owner_id);
                break;
        }
    }
}
