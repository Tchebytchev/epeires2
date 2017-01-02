<?php
/*
 * This file is part of Epeires².
 * Epeires² is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Epeires² is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Epeires². If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Agenda\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

/**
 *
 * @author Bruno Spyckerelle
 *
 */


class AgendaController extends \Application\Controller\TabsController
{
    public function indexAction()
    {
        parent::indexAction();

        $return = array();

        if ($this->flashMessenger()->hasErrorMessages()) {
            $return['error'] = $this->flashMessenger()->getErrorMessages();
        }

        if ($this->flashMessenger()->hasSuccessMessages()) {
            $return['success'] = $this->flashMessenger()->getSuccessMessages();
        }

        $this->flashMessenger()->clearMessages();

        $objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');

        $tabid = $this->params()->fromQuery('tabid', null);

        if ($tabid) {
            $tab = $objectManager->getRepository('Application\Entity\Tab')->find($tabid);
            if ($tab) {
                $categories = $tab->getCategories();
                $cats = array();
                foreach ($categories as $cat) {
                    $cats[] = $cat->getId();
                }
                $this->viewmodel->setVariable('onlyroot', $tab->isOnlyroot());
                $this->viewmodel->setVariable('cats', $cats);
                $this->viewmodel->setVariable('tabid', $tabid);
            } else {
                $return['error'][] = "Impossible de trouver l'onglet correspondant. Contactez votre administrateur.";
            }
        } else {
            $return['error'][] = "Aucun onglet défini. Contactez votre administrateur.";
        }

        $this->viewmodel->setVariables(array(
            'messages' => $return
        ));

        return $this->viewmodel;
    }
}
