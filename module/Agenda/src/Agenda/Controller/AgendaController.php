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


class AgendaController extends \Application\Controller\EventsController
{
    public function indexAction()
    {
        parent::indexAction();
/*
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
*/
    }

    public function getcategoriesAction()
    {
        $objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $qb = $objectManager->createQueryBuilder();
        $qb->select('c')->from('Application\Entity\Category', 'c');

        $rootonly = $this->params()->fromQuery('rootonly', true);
        $timeline = $this->params()->fromQuery('timeline', true);
        $cats = $this->params()->fromQuery('cats', null);
        $agenda = $this->params()->fromQuery('agenda', false);
        if ($cats) {
            $categories = $objectManager->getRepository('Application\Entity\Category')->findBy(array(
                'id' => $cats
            ));
            if ($categories && count($categories) > 0) {
                $orCat = $qb->expr()->orX();
                foreach ($categories as $category) {
                    $orCat->add($qb->expr()
                        ->eq('c.id', $category->getId()));
                }
                $qb->andWhere($orCat);
            }
        }
        $json = array();
        if ($rootonly == true) {
            $qb->andWhere($qb->expr()
                ->isNull('c.parent'));
        }
        if ($timeline == true) {
            $qb->andWhere($qb->expr()
                ->eq('c.timeline', true));
        }
        if ($agenda == true) {
            $qb->andWhere($qb->expr()
                ->neq('c.agenda', true));
        }
        else{
            $qb->andWhere($qb->expr()
                ->eq('c.agenda', true));
        }
        $qb->orderBy("c.place", 'ASC');
        $categories = $qb->getQuery()->getResult();
        $readablecat = $this->filterReadableCategories($categories);
        foreach ($readablecat as $category) {
            $json[$category->getId()] = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'short_name' => $category->getShortName(),
                'color' => $category->getColor(),
                'compact' => $category->isCompactMode(),
                'place' => $category->getPlace(),
                'parent_id' => ($category->getParent() ? $category->getParent()->getId() : - 1),
                'parent_place' => ($category->getParent() ? $category->getParent()->getPlace() : - 1)
            );
        }

        return new JsonModel($json);
    }

}
