<?php
/**
 * Epeires 2
 * @license   https://www.gnu.org/licenses/agpl-3.0.html Affero Gnu Public License
 */

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Application\Entity\Event;
use Application\Form\CategoryFormFieldset;
use Application\Form\CustomFieldset;
use Application\Entity\CustomFieldValue;
use Zend\View\Model\JsonModel;
use Doctrine\Common\Collections\Criteria;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Zend\Form\Annotation\AnnotationBuilder;
use Application\Form\FileFieldset;
use Doctrine\ORM\Query\Expr\Join;
use Application\Entity\PredefinedEvent;
use Doctrine\ORM\QueryBuilder;
use Zend\Session\Container;
use Zend\Form\Element;
use ZfcRbac\Exception\UnauthorizedException;
use Application\Entity\FrequencyCategory;

class EventsController extends ZoneController {
	
    public function indexAction(){    	
    	
        parent::indexAction();
        
    	$viewmodel = new ViewModel();
    	
    	$return = array();
    	
    	if($this->flashMessenger()->hasErrorMessages()){
    		$return['errorMessages'] =  $this->flashMessenger()->getErrorMessages();
    	}
    	
    	if($this->flashMessenger()->hasSuccessMessages()){
    		$return['successMessages'] =  $this->flashMessenger()->getSuccessMessages();
    	}
    	
    	$this->flashMessenger()->clearMessages();
    	
    	$this->layout()->cds = "Nom chef de salle";
    	$this->layout()->ipo = "Nom IPO (téléphone)";
    	
     	$viewmodel->setVariables(array('messages'=>$return));
    	 
        return $viewmodel;
    }
    
    public function saveipoAction(){
    	$messages = array();
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$ipoid = $post['nameipo'];
    		$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    		$ipo = $em->getRepository('Application\Entity\IPO')->find($ipoid);
    		if($ipo) {
    			//un seul IPO par organisation
    			$ipos = $em->getRepository('Application\Entity\IPO')->findBy(array('organisation' => $ipo->getOrganisation()->getId()));
    			foreach ($ipos as $i){
    				$i->setCurrent(false);
    				$em->persist($i);
    			}
    			$ipo->setCurrent(true);
    			$em->persist($ipo);
    			try {
    				$em->flush();
    				$messages['success'][] = "IPO en fonction modifié";
    			} catch (\Exception $e) {
    				$messages['error'][] = $e->getMessage();
    			}
    		} else {
    			$messages['error'][] = "Impossible de modifier l'IPO";
    		}
    	}
    	return new JsonModel($messages);
    }
    
    public function saveopsupAction(){
    	$messages = array();
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$opsupid = $post['nameopsup'];
    		$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    		$opsup = $em->getRepository('Application\Entity\OperationalSupervisor')->find($opsupid);
    		if($opsup) {
    			//un seul IPO par organisation et par zone
    			$opsups = $em->getRepository('Application\Entity\OperationalSupervisor')->findBy(array('organisation' => $opsup->getOrganisation()->getId(), 
    																									'zone' => $opsup->getZone()->getId()));
    			foreach ($opsups as $i){
    				$i->setCurrent(false);
    				$em->persist($i);
    			}
    			$opsup->setCurrent(true);
    			$em->persist($opsup);
    			try {
    				$em->flush();
    				$messages['success'][] = "Op Sup en fonction modifié";
    			} catch (\Exception $e) {
    				$messages['error'][] = $e->getMessage();
    			}
    		} else {
    			$messages['error'][] = "Impossible de modifier le chef OP";
    		}
    	}
    	return new JsonModel($messages);
    }
    
    public function savezoneAction(){
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$zone = $post['zone'];
    		$session = new Container('zone');
    		$session->zoneshortname = $zone;
    	}
    	return new JsonModel();
    }
    
    /**
     * Returns a Json with all relevant events and models
     */
    public function searchAction(){
    	$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	$results = array();
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$search = $post['search'];
    		if(strlen($search) >= 2){  			
    			
    			//search events
    			$results['events'] = array();
    			$qbEvents = $em->createQueryBuilder();;
    			$qbEvents->select(array('e', 'v', 'c', 't', 'cat'))
    			->from('Application\Entity\Event', 'e')
    			->innerJoin('e.custom_fields_values', 'v')
    			->innerJoin('v.customfield', 'c')
    			->innerJoin('c.type', 't')		
    			->innerJoin('e.category', 'cat', Join::WITH, 'cat.fieldname = c')
                        ->andWhere($qbEvents->expr()->isNull('e.parent'))
    			->add('orderBy', 'e.startdate DESC')
    			->setFirstResult( 0 )
    			->setMaxResults( 10 );
    			    			
    			//search models
    			$results['models'] = array();
    			$qbModels = $em->createQueryBuilder();
    			$qbModels->select(array('m', 'v', 'c', 't'))
    			->from('Application\Entity\PredefinedEvent', 'm')
    			->innerJoin('m.custom_fields_values', 'v')
    			->innerJoin('v.customfield', 'c')
    			->innerJoin('c.type', 't')
                        ->andWhere($qbModels->expr()->isNull('m.parent'))
    			->andWhere($qbModels->expr()->eq('m.searchable', true));
    			
    			$this->addCustomFieldsSearch($qbEvents, $qbModels, $search);
    			
    			$query = $qbEvents->getQuery();
    			$events = $query->getResult();
    			foreach ($events as $event){
    				$results['events'][$event->getId()] = $this->getEventJson($event);
    			}
    			
    			$query = $qbModels->getQuery();
    			$models = $query->getResult();
    			foreach ($models as $model){
    				$results['models'][$model->getId()] = $this->getModelJson($model);
    			}
    		}
    	}
    	return new JsonModel($results);
    }
    
    private function addCustomFieldsSearch(QueryBuilder &$qbEvents, QueryBuilder &$qbModels, $search){
    	$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	//search relevant customfields
    	$qb = $em->createQueryBuilder();
    	$qb->select(array('s'))
    	->from('Application\Entity\Sector', 's')
    	->andWhere($qb->expr()->like('s.name', $qb->expr()->literal($search.'%')));
    	$sectors = $qb->getQuery()->getResult();
    	 
    	$qb = $em->createQueryBuilder();
    	$qb->select(array('a'))
    	->from('Application\Entity\Antenna', 'a')
    	->andWhere($qb->expr()->like('a.name', $qb->expr()->literal($search.'%')))
    	->orWhere($qb->expr()->like('a.shortname', $qb->expr()->literal($search.'%')));
    	$query = $qb->getQuery();
    	$antennas = $query->getResult();
    	
    	$qb = $em->createQueryBuilder();
    	$qb->select(array('r'))
    	->from('Application\Entity\Radar', 'r')
    	->andWhere($qb->expr()->like('r.name', $qb->expr()->literal($search.'%')))
    	->orWhere($qb->expr()->like('r.shortname', $qb->expr()->literal($search.'%')));
    	$query = $qb->getQuery();
    	$radars = $query->getResult();
    	
        $orModels = $qbModels->expr()->orX($qbModels->expr()->like('m.name', $qbModels->expr()->literal($search.'%')));
        $orEvents = $qbEvents->expr()->orX($qbEvents->expr()->like('v.value', $qbEvents->expr()->literal($search.'%')));
        
    	foreach ($antennas as $antenna){
    		$orEvents->add($qbEvents->expr()->andX(
    				$qbEvents->expr()->eq('t.type', '?1'),
    				$qbEvents->expr()->eq('v.value',$antenna->getId())
    		));
    		$qbEvents->setParameter('1', 'antenna');
    		
    		$orModels->add($qbModels->expr()->andX(
    				$qbModels->expr()->eq('t.type', '?1'),
    				$qbModels->expr()->eq('v.value',$antenna->getId())
    		));
    		$qbModels->setParameter('1', 'antenna');
    	}
    	
    	foreach ($sectors as $sector){
    		$orEvents->add($qbEvents->expr()->andX(
    				$qbEvents->expr()->eq('t.type', '?2'),
    				$qbEvents->expr()->eq('v.value',$sector->getId())
    		));
    		$qbEvents->setParameter('2', 'sector');
    		
    		$orModels->add($qbModels->expr()->andX(
    				$qbModels->expr()->eq('t.type', '?2'),
    				$qbModels->expr()->eq('v.value',$sector->getId())
    		));
    		$qbModels->setParameter('2', 'sector');
    	}
    	
    	foreach ($radars as $radar) {
    		$orEvents->add($qbEvents->expr()->andX(
    			$qbEvents->expr()->eq('t.type', '?3'),
    			$qbEvents->expr()->eq('v.value', $radar->getId())
    		));
    		$qbEvents->setParameter('3', 'radar');
    		
    		$orModels->add($qbModels->expr()->andX(
    				$qbModels->expr()->eq('t.type', '?3'),
    				$qbModels->expr()->eq('v.value', $radar->getId())
    		));
    		$qbModels->setParameter('3', 'radar');
    	}
        
        $qbModels->andWhere($orModels);
        $qbEvents->andWhere($orEvents);
    }
    
 	/**
 	 * 
 	 * @return \Zend\View\Model\JsonModel Exception : if query param 'return' is true, redirect to route application. 
 	 */
    public function saveAction(){   
    	
		$messages = array();
		$event = null;
		$return = $this->params()->fromQuery('return', null);
		
    	if($this->zfcUserAuthentication()->hasIdentity()){
    		
    		if($this->getRequest()->isPost()){
    			$post = array_merge_recursive($this->getRequest()->getPost()->toArray(),
    									$this->getRequest()->getFiles()->toArray());
    			$id = $post['id'] ? $post['id'] : null;
    		  		
    			$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    			
    			$credentials = false;
    			
    			if($id){
    				//modification
    				$event = $objectManager->getRepository('Application\Entity\Event')->find($id);
    				if($event){
    					if($this->isGranted('events.write') || $event->getAuthor()->getId() === $this->zfcUserAuthentication()->getIdentity()->getId()){
							$credentials = true;
							//si utilisateur n'a pas les droits events.status, le champ est désactivé et aucune valeur n'est envoyée
							if(!isset($post['status'])){
								$post['status'] = $event->getStatus()->getId();
							}			
    					}
    				}    				
    			} else {
    				//création
    				if($this->isGranted('events.create')){
    					$event = new Event();
    					$event->setAuthor($this->zfcUserAuthentication()->getIdentity());
    					//si utilisateur n'a pas les droits events.status, le champ est désactivé et aucune valeur n'est envoyée
    					if(!isset($post['status'])){
    						$post['status'] = 1;
    					}
    					$credentials = true;
    				}
    			}
    			                        
    			if($credentials){
    				
    				$form = $this->getSkeletonForm($event);
    				$form->setPreferFormInputFilter(true);
    				$form->setData($post);
    				 
    				if($form->isValid()){
    						                                    
    					//TODO find why hydrator can't set a null value to a datetime
    					if(isset($post['enddate']) && empty($post['enddate'])){
    						$event->setEndDate(null);
    					}
    					    					
    					//hydrator can't guess timezone, force UTC of end and start dates
    					if(isset($post['startdate']) && !empty($post['startdate'])){
    						$offset = date("Z");
    						$startdate = new \DateTime($post['startdate']);
    						$startdate->setTimezone(new \DateTimeZone("UTC"));
    						$startdate->add(new \DateInterval("PT".$offset."S"));
                                                if(isset($post['enddate']) && !empty($post['enddate'])) {
                                                    $enddate = new \DateTime($post['enddate']);
                                                    $enddate->setTimezone(new \DateTimeZone("UTC"));
                                                    $enddate->add(new \DateInterval("PT".$offset."S"));
                                                    $event->setDates($startdate, $enddate);
                                                } else {
                                                    $event->setStartdate($startdate);
                                                }
    					}
                                                                                
    					//save optional datas
    					if(isset($post['custom_fields'])){
    						foreach ($post['custom_fields'] as $key => $value){
    							//génération des customvalues si un customfield dont le nom est $key est trouvé
    							$customfield = $objectManager->getRepository('Application\Entity\CustomField')->findOneBy(array('id'=>$key));
    							if($customfield){
    								$customvalue = $objectManager->getRepository('Application\Entity\CustomFieldValue')
    								->findOneBy(array('customfield'=>$customfield->getId(), 'event'=>$id));
    								if(!$customvalue){
    									$customvalue = new CustomFieldValue();
    									$customvalue->setEvent($event);
    									$customvalue->setCustomField($customfield);
    									$event->addCustomFieldValue($customvalue);
    								}
    								$customvalue->setValue($value);
    								$objectManager->persist($customvalue);
    							}
    						}
    					}
    					//create associated actions (only relevant if creation from a model)
    					if(isset($post['modelid'])){
    						$parentID = $post['modelid'];
    						//get actions
    						foreach ($objectManager->getRepository('Application\Entity\PredefinedEvent')->findBy(array('parent'=>$parentID)) as $action){
						if($action->getCategory() instanceof \Application\Entity\ActionCategory) {
								$child = new Event();
								$child->setParent($event);
								$child->setOrganisation($event->getOrganisation());
								$child->createFromPredefinedEvent($action);
								$child->setStatus($objectManager->getRepository('Application\Entity\Status')->findOneBy(array('defaut'=>true, 'open'=> true)));
								//customfields
								foreach($action->getCustomFieldsValues() as $customvalue){
									$newcustomvalue = new CustomFieldValue();
									$newcustomvalue->setEvent($child);
									$newcustomvalue->setCustomField($customvalue->getCustomField());
									$newcustomvalue->setValue($customvalue->getValue());
									$objectManager->persist($newcustomvalue);
								}
								$objectManager->persist($child);
    							}
    						}
    					}
    					//associated actions to be copied
    					if(isset($post['fromeventid'])){
    						$parentID = $post['fromeventid'];
    						foreach ($objectManager->getRepository('Application\Entity\Event')->findBy(array('parent'=>$parentID)) as $action){
							if($action->getCategory() instanceof \Application\Entity\ActionCategory){
								$child = new Event();
								$child->setParent($event);
								$child->setOrganisation($event->getOrganisation());
								$child->setCategory($action->getCategory());
								$child->setImpact($action->getImpact());
								$child->setPunctual($action->isPunctual());
								$child->setStatus($objectManager->getRepository('Application\Entity\Status')->findOneBy(array('defaut'=>true, 'open'=> true)));   								
								foreach ($action->getCustomFieldsValues() as $customvalue){
									$newcustomvalue = new CustomFieldValue();
									$newcustomvalue->setEvent($child);
									$newcustomvalue->setCustomField($customvalue->getCustomField());
									$newcustomvalue->setValue($customvalue->getValue());
									$objectManager->persist($newcustomvalue);
								}
								$objectManager->persist($child);
    							}
    						}
    					}
    					
    					//fichiers
    					if(isset($post['fichiers']) && is_array($post['fichiers'])){
    						foreach ($post['fichiers'] as $key => $f){
                                                    $file = $objectManager->getRepository('Application\Entity\File')->find($key);
                                                    if($file){
                                                        $file->addEvent($event);
                                                        $objectManager->persist($file);
                                                    }
      
    						}
    					}
    					
                                        //alertes
                                        if(isset($post['alarm']) && is_array($post['alarm'])){
                                            foreach ($post['alarm'] as $key => $alarmpost){
                                                $alarm = new Event();
                                                $alarm->setCategory($objectManager->getRepository('Application\Entity\AlarmCategory')->findAll()[0]);
                                                $alarm->setAuthor($this->zfcUserAuthentication()->getIdentity());
                                                $alarm->setOrganisation($event->getOrganisation());
                                                $alarm->setParent($event);
                                                $alarm->setStatus($objectManager->getRepository('Application\Entity\Status')->findOneBy(array('open'=> true, 'defaut'=>true)));
                                                $offset = date("Z");
    						$startdate = new \DateTime($alarmpost['date']);
    						$startdate->setTimezone(new \DateTimeZone("UTC"));
    						$startdate->add(new \DateInterval("PT".$offset."S"));
                                                $alarm->setStartdate($startdate);
                                                $alarm->setPunctual(true);
                                                $alarm->setImpact($objectManager->getRepository('Application\Entity\Impact')->find(5));
                                                $name = new CustomFieldValue();
                                                $name->setCustomField($alarm->getCategory()->getNamefield());
                                                $name->setValue($alarmpost['name']);
                                                $name->setEvent($alarm);
                                                $alarm->addCustomFieldValue($name);
                                                $comment = new CustomFieldValue();
                                                $comment->setCustomField($alarm->getCategory()->getTextfield());
                                                $comment->setValue($alarmpost['comment']);
                                                $comment->setEvent($alarm);
                                                $alarm->addCustomFieldValue($comment);
                                                $objectManager->persist($name);
                                                $objectManager->persist($comment);
                                                $objectManager->persist($alarm);
                                            }
                                        }
                                        
    					if($event->getStatus()->getId() == 3 || $event->getStatus()->getId() == 4) { //passage au statut terminé ou annulé
    						//on termine les évènements fils de type fréquence
    						foreach ($event->getChildren() as $child){
    							if($child->getCategory() instanceof FrequencyCategory){
    								if($event->getStatus()->getId() == 3){
                                                                    //date de fin uniquement pour les fermetures
                                                                    $child->setEnddate($event->getEnddate());
                                                                }
    								$child->setStatus($event->getStatus());
    								$objectManager->persist($child);
    							}
    						}
    					}
    					
    					$objectManager->persist($event);
    					try{
    						$objectManager->flush();
    						$messages['success'][] = ($id ? "Evènement modifié" : "Évènement enregistré");
    					} catch(\Exception $e){
    						$messages['error'][] = $e->getMessage();
    					}
    				} else {
    					//erase event
    					$event = null;
    					//formulaire invalide
    					$messages['error'][] = "Impossible d'enregistrer l'évènement.";
    					//traitement des erreurs de validation
    					$this->processFormMessages($form->getMessages(), $messages);
    				}
    					
    			} else {
    				$messages['error'][] = "Création ou modification impossible, droits insuffisants.";
    			}
    			
    		} else {
    			$messages['error'][] = "Requête illégale.";
    		}
    		
    	} else {
    		$messages['error'][] = "Utilisateur non authentifié, action impossible.";
    	}
    	
    	if($return){
    		foreach ($messages['success'] as $message){
    			$this->flashMessenger()->addSuccessMessage($message);
    		}
    		foreach ($messages['error'] as $message){
    			$this->flashMessenger()->addErrorMessage($message);
    		}
    		return $this->redirect()->toRoute('application');
    	} else {
    		$json = array();
    		$json['messages'] = $messages;
    		if($event){
    			$json['events'] = array($event->getId() => $this->getEventJson($event));
    		}
    		return new JsonModel($json);
    	}
    	
    }
    
    public function subformAction(){
    	$part = $this->params()->fromQuery('part', null);
    	
    	$viewmodel = new ViewModel();
    	$request = $this->getRequest();
    	 
    	//disable layout if request by Ajax
    	$viewmodel->setTerminal($request->isXmlHttpRequest());
    	 
    	$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	 
    	$form = $this->getSkeletonForm();
    	
    	if($part){
    		switch ($part) {
    			case 'subcategories':
    				$id = $this->params()->fromQuery('id');
    				$subcat = $this->filterReadableCategories($em->getRepository('Application\Entity\Category')->getChilds($id));
    				$subcatarray = array();
    				foreach ($subcat as $cat){
    					$subcatarray[$cat->getId()] = $cat->getName();
    				}
    				$viewmodel->setVariables(array(
    						'part' => $part,
    						'values' => $subcatarray,
    				));
    				break;
    			case 'predefined_events':
    				$id = $this->params()->fromQuery('id');
    				$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    				$category = $em->getRepository('Application\Entity\Category')->find($id);
    				$viewmodel->setVariables(array(
    						'part' => $part,
    						'values' => $em->getRepository('Application\Entity\PredefinedEvent')->getEventsWithCategoryAsArray($category),
    				));
    				break;
    			case 'custom_fields':
    				$viewmodel->setVariables(array('part' => $part,));
    				$form->add(new CustomFieldset($this->getServiceLocator(), $this->params()->fromQuery('id')));
    				break;
    			default:
    				;
    				break;
    		}
    	}
    	$viewmodel->setVariables(array('form' => $form));
    	return $viewmodel;
    }
    
    /**
     * Create a new form
     * @return \Zend\View\Model\ViewModel
     */
    public function formAction(){
    	
    	$viewmodel = new ViewModel();
    	$request = $this->getRequest();
    	
    	//disable layout if request by Ajax    	
    	$viewmodel->setTerminal($request->isXmlHttpRequest());
    	  	
    	$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	
    	//création du formulaire : identique en cas de modif ou création
    	$form = $this->getSkeletonForm();
    	 
    	$id = $this->params()->fromQuery('id', null);
    	
    	$copy = $this->params()->fromQuery('copy', null);
    	
    	$model = $this->params()->fromQuery('model', null);
    	
    	$event = null;
    	
    	$pevent = null;
    	
    	$zonefilters = null;
    	
    	if($id || $model){
    		$cat = null;
    		if($id && $model){ //copie d'un modèle
    			$pevent = $em->getRepository('Application\Entity\PredefinedEvent')->find($id);
    			if($pevent){
    				$cat = $pevent->getCategory();
    				$viewmodel->setVariable('model', $pevent);
    				$zonefilters = $em->getRepository('Application\Entity\QualificationZone')->getAllAsArray($pevent->getOrganisation());
    				$form->get('category')->setValue($cat->getId());
    			}
    		} else if($id) { //modification d'un evt
    			$event = $em->getRepository('Application\Entity\Event')->find($id);
    			if($event){
    				$cat = $event->getCategory();
    				$zonefilters = $em->getRepository('Application\Entity\QualificationZone')->getAllAsArray($event->getOrganisation());
       			}
    			
    		}    		
    		if($cat && $cat->getParent()){
    			$form->get('categories')->get('subcategories')->setValueOptions(
    					$em->getRepository('Application\Entity\Category')->getChildsAsArray($cat->getParent()->getId()));
    			$form->get('categories')->get('root_categories')->setAttribute('value', $cat->getParent()->getId());
    			$form->get('categories')->get('subcategories')->setAttribute('value', $cat->getId());
    		} else {
    			$form->get('categories')->get('root_categories')->setAttribute('value', $cat->getId());
    		}
    		//custom fields
    		$form->add(new CustomFieldset($this->getServiceLocator(), $cat->getId()));
    	}
    	if(!$zonefilters) { //si aucun filtre => cas d'un nouvel evt
    		if($this->zfcUserAuthentication()->hasIdentity()){
    			$org = $this->zfcUserAuthentication()->getIdentity()->getOrganisation();
    			$form->get('organisation')->setValue($org->getId());
    			$zonefilters = $em->getRepository('Application\Entity\QualificationZone')->getAllAsArray($org);
    		} else {
    			//aucun utilisateur connecté ??? --> possible si deconnexion déans l'intervalle
    			throw new UnauthorizedException('Aucun utilisateur connecté.');
    		}
    	}
    	
    	if(!$zonefilters || count($zonefilters) == 0){//pas de zone => cacher l'élément
    		$form->remove('zonefilters');
    	} else {
    		$form->get('zonefilters')->setValueOptions($zonefilters);
    	}
    	
    	if($id && $pevent){ //copie d'un modèle
    		//prefill customfields with predefined values
    		foreach ($em->getRepository('Application\Entity\CustomField')->findBy(array('category'=>$cat->getId())) as $customfield){
    			$customfieldvalue = $em->getRepository('Application\Entity\CustomFieldValue')->findOneBy(array('event'=>$pevent->getId(), 'customfield'=>$customfield->getId()));
    			if($customfieldvalue){
    				$form->get('custom_fields')->get($customfield->getId())->setAttribute('value', $customfieldvalue->getValue());
    			}
    		}
    	}
    	
    	if(!$id || ($id && $copy) || ($id && $pevent)){//nouvel évènement
    		if($this->isGranted('events.status')){
    			//utilisateur opérationnel => statut confirmé dès le départ
    			$form->get('status')->setAttribute('value', 2);
    		} else {
    			$form->get('status')->setAttribute('value', 1);
    		}
    	}
    	
    	if($id && $event){ //modification d'un evt, prefill form
    		
    		//custom fields values
    		foreach ($em->getRepository('Application\Entity\CustomField')->findBy(array('category'=>$cat->getId())) as $customfield){
    			$customfieldvalue = $em->getRepository('Application\Entity\CustomFieldValue')->findOneBy(array('event'=>$event->getId(), 'customfield'=>$customfield->getId()));
    			if($customfieldvalue){
    				$form->get('custom_fields')->get($customfield->getId())->setAttribute('value', $customfieldvalue->getValue());
    			}
    		}
    		
    		//other values
    		$form->bind($event);
    		$form->setData($event->getArrayCopy());
    		if($copy){
    			$form->get('id')->setValue('');
    			$form->get('startdate')->setValue('');
    			$form->get('enddate')->setValue('');
    			$viewmodel->setVariables(array('event'=>$event, 'copy'=>$id));
    		} else {
    			$viewmodel->setVariables(array('event'=>$event));
    		}
    	}
    	    	
    	$viewmodel->setVariables(array('form' => $form));
    	return $viewmodel;
    	 
    }
    
    private function getSkeletonForm($event = null){
    	$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	
    	if(!$event){
    		$event = new Event();
    	}
    	
    	$builder = new AnnotationBuilder();
    	$form = $builder->createForm($event);
    	$form->setHydrator(new DoctrineObject($em))
    		->setObject($event);    	
    	
    	$form->get('status')
    		->setValueOptions($em->getRepository('Application\Entity\Status')->getAllAsArray());
    	
    	$form->get('impact')
    		->setValueOptions($em->getRepository('Application\Entity\Impact')->getAllAsArray());
    	
    	$form->get('organisation')->setValueOptions($em->getRepository('Application\Entity\Organisation')->getAllAsArray());
    	
    	//add default fieldsets
    	$rootCategories = $this->filterReadableCategories($em->getRepository('Application\Entity\Category')->getRoots(null, true));
    	$rootarray = array();
    	foreach ($rootCategories as $cat){
    		$rootarray[$cat->getId()] = $cat->getName();
    	}
    	
    	$form->add(new CategoryFormFieldset($rootarray));
    	
        $form->bind($event);
    	$form->setData($event->getArrayCopy());
    	
    	//replace default category element
    	$form->remove('category');
    	$category = new Element\Hidden('category');
    	$form->add($category);
    	
    	$form->add(array(
    			'name' => 'submit',
    			'attributes' => array(
    					'type' => 'submit',
    					'value' => 'Ajouter',
    					'class' => 'btn btn-primary',
    			),
    	));
    	    	
    	return $form;
    }
    
    public function getpredefinedvaluesAction(){
    	$predefinedId = $this->params()->fromQuery('id',null);
    	$json = array();
    	$defaultvalues = array();
    	$customvalues = array();
    	
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	$entityService = $this->getServiceLocator()->get('EventService');
    	
    	$predefinedEvt = $objectManager->getRepository('Application\Entity\PredefinedEvent')->find($predefinedId);
    	
    	$defaultvalues['punctual'] = $predefinedEvt->isPunctual();
		
    	$defaultvalues['impact'] = $predefinedEvt->getImpact()->getId();
    	
    	foreach ($predefinedEvt->getZonefilters() as $filter){
    		$defaultvalues['zonefilters'][] = $filter->getId();
    	}
    	
    	$json['defaultvalues'] = $defaultvalues;
    	
    	foreach ($predefinedEvt->getCustomFieldsValues() as $customfieldvalue){
    		$customvalues[$customfieldvalue->getCustomField()->getId()] = $customfieldvalue->getValue();
    	}
    	
    	$json['customvalues'] = $customvalues;
    	
    	return new JsonModel($json);
    }
    
    public function getactionsAction(){
    	$parentId = $this->params()->fromQuery('id', null);
    	$json = array();
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	
    	foreach ($objectManager->getRepository('Application\Entity\PredefinedEvent')->findBy(array('parent' => $parentId), array('place' => 'DESC')) as $action){
            if($action->getCategory() instanceof \Application\Entity\ActionCategory) {
    		$json[$action->getId()] = array('name' =>  $this->getServiceLocator()->get('EventService')->getName($action),
    										'impactname' => $action->getImpact()->getName(),
    										'impactstyle' => $action->getImpact()->getStyle());
            }
    	}
    	
    	return new JsonModel($json);
    }
    
    /*
     * Fichiers liés à un évènement, au format JSON
     */
    public function getfilesAction(){
        $eventid = $this->params()->fromQuery('id', null);
        $json = array();
        $objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        foreach($objectManager->getRepository('Application\Entity\PredefinedEvent')->find($eventid)->getFiles() as $file){
            $data = array();
            $data['reference'] = $file->getReference();
            $data['path'] = $file->getPath();
            $data['name'] = ($file->getName() ? $file->getName() : $file->getFilename());
            $fichier = array();
            $fichier['id'] = $file->getId();
            $fichier['datas'] = $data;
            $json[] = $fichier;
        }
        return new JsonModel($json);
    }
    
    /**
     * Alarmes liées à un évènement, au format JSON
     */
    public function getalarmsAction(){
	$eventid = $this->params()->fromQuery('id', null);
	$json = array();
	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
	foreach($objectManager->getRepository('Application\Entity\PredefinedEvent')->findBy(array('parent' => $eventid)) as $alarm){
		if($alarm->getCategory() instanceof \Application\Entity\AlarmCategory){
			$alarmjson = array();
			$now = new \DateTime('NOW');
			$now->setTimezone(new \DateTimeZone("UTC"));
			$now->add(new \DateInterval('PT'.$alarm->getStartdateDelta().'M'));
			$alarmjson['datetime'] = $now->format(DATE_RFC2822);
			foreach($alarm->getCustomFieldsValues() as $value){
				if($value->getCustomField()->getId() == $alarm->getCategory()->getFieldname()->getId()){
					$alarmjson['name'] = $value->getValue();
				} else if($value->getCustomField()->getId() == $alarm->getCategory()->getTextField()->getId()) {
					$alarmjson['comment'] = $value->getValue();
				}
			}
			$json[] = $alarmjson;
		}
	}
	return new JsonModel($json);
    }
    
    /**
     * Return {'open' => '<true or false>'}
     * @return \Zend\View\Model\JsonModel
     */
    public function toggleficheAction(){
    	$evtId = $this->params()->fromQuery('id', null);
    	$json = array();
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	
    	$event = $objectManager->getRepository('Application\Entity\Event')->find($evtId);
    	
    	if($event){
    		$event->setStatus($objectManager->getRepository('Application\Entity\Status')->findOneBy(array('defaut'=>true, 
    																									'open' => !$event->getStatus()->isOpen())));
    		$objectManager->persist($event);
    		$objectManager->flush();
    	}
    	
    	$json['open'] = $event->getStatus()->isOpen();
    	    	
    	return new JsonModel($json);
    }
    
    public function deletefileAction(){
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	 
    	$fileid = $this->params()->fromQuery('id', null);
    	$eventid = $this->params()->fromQuery('eventid', null);
    	$messages = array();
    	
    	if($fileid){
    		$file = $objectManager->getRepository('Application\Entity\File')->find($fileid);
                if($eventid && $file){
                    $event = $objectManager->getRepository('Application\Entity\Event')->find($eventid);
                    if($event){
                        $file->removeEvent($event);
                        $objectManager->persist($file);
                    } else {
                        $messages['error'][] = "Impossible d'enlever le fichier de l'évènement";
                    }
                } else {
                    if($file){
                    	$objectManager->remove($file);
                        $messages['success'][] = "Fichier correctement ajouté";
                    } else {
                        $messages['error'][] = "Impossible de supprimer le fichier : aucun fichier correspondant.";
                    }
                }
                try {
                     $objectManager->flush();
                } catch (\Exception $ex) {
                    $messages['error'][] = $ex->getMessage();
                }
        } else {
    		$messages['error'][] = "Impossible de supprimer le fichier : aucun paramètre trouvé.";
    	}
    	return new JsonModel($messages);
    }
    
    /**
     * {'evt_id_0' => {
     * 		'name' => evt_name,
     * 		'modifiable' => boolean,
     * 		'start_date' => evt_start_date,
     *		'end_date' => evt_end_date,
     *		'punctual' => boolean,
     *		'category' => evt_category_name,
     *		'category_short' => evt_category_short_name,
     *		'status_name' => evt_status_name,
     *		'actions' => {
     *			'action_name0' => open? (boolean),
     *			'action_name1' => open? (boolean),
     *			...
     *			}
     * 		},
     * 	'evt_id_1' => ...
     * }
     * @return \Zend\View\Model\JsonModel
     */
    public function geteventsAction(){
    	
    	$lastmodified = $this->params()->fromQuery('lastmodified', null);
    	
    	$day = $this->params()->fromQuery('day', null);
    	
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	
    	$qb = $objectManager->createQueryBuilder();
    	$qb->select(array('e', 'f'))
    	->from('Application\Entity\Event', 'e')
    	->leftJoin('e.zonefilters', 'f')
    	->andWhere($qb->expr()->isNull('e.parent'));//display only root events
    	
    	if($lastmodified){
    		$qb->andWhere($qb->expr()->gte('last_modified_on', $lastmodified));
    	} else if($day) {
    		$daystart = new \DateTime($day);
    		$daystart->setTime(0, 0, 0);
    		$dayend = new \DateTime($day);
    		$dayend->setTime(23, 59, 59);
    		$daystart = $daystart->format("Y-m-d H:i:s");
    		$dayend = $dayend->format("Y-m-d H:i:s");
    		//tous les évènements ayant une intersection non nulle avec $day
    		$qb->andWhere($qb->expr()->orX(
    				$qb->expr()->andX(
    					$qb->expr()->lt('e.startdate', '?1'),
    					$qb->expr()->orX(
    						$qb->expr()->isNull('e.enddate'),
    						$qb->expr()->gte('e.enddate', '?1')
    					)
    				),
    				$qb->expr()->andX(
    					$qb->expr()->gte('e.startdate', '?1'),
    					$qb->expr()->lte('e.startdate', '?2')
    				)
    		));
    		$qb->setParameters(array(1 => $daystart, 2 => $dayend));    		
    	} else {
    		//every open events and all events of the last 3 days
    		$now = new \DateTime('NOW');
    		$qb->andWhere($qb->expr()->orX(
    				$qb->expr()->gte('e.startdate', '?1'),
    				$qb->expr()->gte('e.enddate', '?1'),
    				$qb->expr()->in('e.status', '?2')
    		));
    		$qb->setParameters(array(1 => $now->sub(new \DateInterval('P3D'))->format('Y-m-d H:i:s'),
    				2 => array(1,2)));
    	}

    
    	//filtre par zone
    	$session = new Container('zone');
    	$zonesession = $session->zoneshortname;
    	if($this->zfcUserAuthentication()->hasIdentity()){
    		//on filtre soit par la valeur en session soit par l'organisation de l'utilisateur
    		//TODO gérer les evts partagés
    		if($zonesession != null){ //application d'un filtre géographique
    			if($zonesession != '0'){
    				//la variable de session peut contenir soit une orga soit une zone
    				$orga = $objectManager->getRepository('Application\Entity\Organisation')->findOneBy(array('shortname'=>$zonesession));
    				if($orga){
    					$qb->andWhere($qb->expr()->eq('e.organisation', $orga->getId()));
    				} else {
    					$zone = $objectManager->getRepository('Application\Entity\QualificationZone')->findOneBy(array('shortname'=>$zonesession));
    					if($zone){
    						$qb->andWhere($qb->expr()->andX(
    							$qb->expr()->eq('e.organisation', $zone->getOrganisation()->getId()),
                                                        $qb->expr()->orX(
                                                            $qb->expr()->eq('f', $zone->getId()),
                                                            $qb->expr()->isNull('f.id'))
                                                        )
    						);
    					} else {
    						//throw error
    					}
    				}
    			} else {
    				//tous les evts de l'org de l'utilisateur connecté
    				$orga = $this->zfcUserAuthentication()->getIdentity()->getOrganisation();
    				$qb->andWhere($qb->expr()->eq('e.organisation', $orga->getId()));
    			}
    		} else {
    			//tous les evts de l'org de l'utilisateur connecté
    			$orga = $this->zfcUserAuthentication()->getIdentity()->getOrganisation();
    			$qb->andWhere($qb->expr()->eq('e.organisation', $orga->getId()));
    		}
    	} else {
    		//aucun filtre autre que les rôles
    	}

    	$events = $qb->getQuery()->getResult();
    	
    	$readableEvents = array();
    	
    	if($this->zfcUserAuthentication()->hasIdentity()){
    		$roles = $this->zfcUserAuthentication()->getIdentity()->getRoles();
    		foreach ($events as $event){
    			$eventroles = $event->getCategory()->getReadroles();
    			foreach ($roles as $role){
    				if($eventroles->contains($role)){
    					$readableEvents[] = $event;
    					break;
    				}
    			}
    		}
    	} else {
    		$role = $this->getServiceLocator()->get('ZfcRbac\Options\ModuleOptions')->getGuestRole();
    		$roleentity = $objectManager->getRepository('Core\Entity\Role')->findOneBy(array('name'=>$role));
    		if($roleentity){
    			foreach ($events as $event){
    				$eventroles = $event->getCategory()->getReadroles();
    				if($eventroles->contains($roleentity)){
    					$readableEvents[] = $event;
    				}
    			}
    		}
    	}
    	
    	$json = array();
    	foreach ($readableEvents as $event){ 		
    		$json[$event->getId()] = $this->getEventJson($event);
    	}
    	
    	return new JsonModel($json);
    }
    
    private function getEventJson(Event $event){
    	$eventservice = $this->getServiceLocator()->get('EventService');
    	$json = array('name' => $eventservice->getName($event),
    					'modifiable' => $eventservice->isModifiable($event),
    					'start_date' => ($event->getStartdate() ? $event->getStartdate()->format(DATE_RFC2822) : null),
    					'end_date' => ($event->getEnddate() ? $event->getEnddate()->format(DATE_RFC2822) : null),
    					'punctual' => $event->isPunctual(),
    					'category_root' => ($event->getCategory()->getParent() ? $event->getCategory()->getParent()->getName() : $event->getCategory()->getName()),
    					'category_root_short' => ($event->getCategory()->getParent() ? $event->getCategory()->getParent()->getShortName() : $event->getCategory()->getShortName()),
    					'category' => $event->getCategory()->getName(),
    					'category_short' => $event->getCategory()->getShortName(),
    					'category_compact' => $event->getCategory()->isCompactMode(),
    					'status_name' => $event->getStatus()->getName(),
    					'impact_value' => $event->getImpact()->getValue(),
    					'impact_name' => $event->getImpact()->getName(),
    					'impact_style' => $event->getImpact()->getStyle(),
    					'star' => $event->isStar(),
    	);
    	
    	$actions = array();
    	foreach ($event->getChildren() as $child){
            if($child->getStatus() != null) { //Status is required but...
                $actions[$eventservice->getName($child)] = $child->getStatus()->isOpen();
            }
    	}
    	$json['actions'] = $actions;
    	
    	return $json;
    }
    
    private function getModelJson(PredefinedEvent $model){
    	$json = array(
    		'name' => $model->getName(),
    		'category_root' => ($model->getCategory()->getParent() ? $model->getCategory()->getParent()->getName() : $model->getCategory()->getName()),
    		'category' => $model->getCategory()->getName(), 
    	);
    	return $json;
    }
    
    /**
     * Liste des catégories racines visibles timeline
     * Au format JSON
     */
    public function getcategoriesAction(){
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	$json = array();
    	$criteria = Criteria::create()->andWhere(Criteria::expr()->isNull('parent'));
    	$criteria->andWhere(Criteria::expr()->eq('timeline', true));
        $criteria->orderBy(array("place" => Criteria::ASC));
    	$categories = $objectManager->getRepository('Application\Entity\Category')->matching($criteria);
    	$readablecat = $this->filterReadableCategories($categories);
    	foreach ($readablecat as $category){
    		$json[$category->getId()] = array(
    			'name' => $category->getName(),
    			'short_name' => $category->getShortName(),
    			'color' => $category->getColor(),
    			'compact' => $category->isCompactMode(),
    		);
    	}
    	
    	return new JsonModel($json);
    }
    
    private function filterReadableCategories($categories){
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	$readablecat = array();
    	foreach ($categories as $category){
    		if($this->zfcUserAuthentication()->hasIdentity()){
    			$roles = $this->zfcUserAuthentication()->getIdentity()->getRoles();
    			foreach ($roles as $role){
    				if($category->getReadroles(true)->contains($role)){
    					$readablecat[] = $category;
    					break;
    				}
    			}
    		} else {
    			$role = $this->getServiceLocator()->get('ZfcRbac\Options\ModuleOptions')->getGuestRole();
    			$roleentity = $objectManager->getRepository('Core\Entity\Role')->findOneBy(array('name'=>$role));
    			if($roleentity){
    				if($category->getReadroles(true)->contains($roleentity)){
    					$readablecat[] = $category;
    				}
    			}
    		}
    	
    	}
    	return $readablecat;
    }
    
    /**
     * Liste des impacts au format JSON
     */
    public function getimpactsAction(){
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	$json = array();
    	$impacts = $objectManager->getRepository('Application\Entity\Impact')->findAll();
    	foreach ($impacts as $impact){
    		$json[$impact->getId()] = array(
    				'name' => $impact->getName(),
    				'style' => $impact->getStyle(),
    				'value' => $impact->getValue(),
    		);
    	}
    	return new JsonModel($json);
    }
    
    public function gethistoryAction(){

    	$viewmodel = new ViewModel();
    	$request = $this->getRequest();
    	 
    	//disable layout if request by Ajax
    	$viewmodel->setTerminal($request->isXmlHttpRequest());
    	
    	$evtId = $this->params()->fromQuery('id', null);
    	
    	$eventservice = $this->getServiceLocator()->get('EventService');
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	
    	$event = $objectManager->getRepository('Application\Entity\Event')->find($evtId);
    	
    	$history = null;
    	if($event){
    		$history = $eventservice->getHistory($event);
    	}
    	
    	$viewmodel->setVariable('history', $history);
    	
    	return $viewmodel;
    }
    
    /**
     * Usage :
     * $this->url('application', array('controller' => 'events'))+'/changefield?id=<id>&field=<field>&value=<newvalue>'
     * @return JSon with messages
     */
    public function changefieldAction(){
    	$id = $this->params()->fromQuery('id', 0);
    	$field = $this->params()->fromQuery('field', 0);
    	$value = $this->params()->fromQuery('value', 0);
    	$messages = array();
    	if($id){
    		$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    		$event = $objectManager->getRepository('Application\Entity\Event')->find($id);
    		if ($event) {
				switch ($field) {
					case 'enddate' :
						if($event->setEnddate(new \DateTime($value))){
                                                    $objectManager->persist($event);
                                                    $messages['success'][] = "Date et heure de fin modifiées.";
                                                } else {
                                                    $messages['error'][] = "Impossible de changer la date de fin.";
                                                }
						break;	
					case 'startdate' :
						if($event->setStartdate(new \DateTime($value))){
                                                    $objectManager->persist($event);
                                                    $messages['success'][] = "Date et heure de début modifiées."; 
                                                } else {
                                                    $messages['error'][] = "Impossible de changer la date de début.";
                                                }						
						break;
					case 'impact' :
						$impact = $objectManager->getRepository('Application\Entity\Impact')->findOneBy(array('value'=>$value));
						if($impact){
							$event->setImpact($impact);
							$objectManager->persist($event);
							$messages['success'][] = "Impact modifié.";
						}
						break;
					case 'star' :
						$event->setStar($value);
						$objectManager->persist($event);
						$messages['success'][] = "Evènement modifié.";
						break;
					case "status" :
						$status = $objectManager->getRepository('Application\Entity\Status')->findOneBy(array('name'=>$value));
						if($status){
							$event->setStatus($status);
							$objectManager->persist($event);
							$messages['success'][] = "Statut de l'évènement modifié.";
						}
					default :
						;
						break;
				}
                                try {
                                    $objectManager->flush();
                                } catch (\Exception $ex) {
                                    $messages['error'][] = $ex->getMessage();
                                }
    		} else {
                    $messages['error'][] = "Impossible de trouver l'évènement à modifier";
                }
    	} else {
    		$messages['error'][] = "Impossible de modifier l'évènement.";
    	}
    	return new JsonModel($messages);
    }
    
    public function getficheAction(){
    	$viewmodel = new ViewModel();
    	$request = $this->getRequest();
    	 
    	//disable layout if request by Ajax
    	$viewmodel->setTerminal($request->isXmlHttpRequest());
    	
    	$id = $this->params()->fromQuery('id', null);
    	
    	$eventservice = $this->getServiceLocator()->get('EventService');
    	$objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    	
    	$event = $objectManager->getRepository('Application\Entity\Event')->find($id);
    	
        $history = null;
    	if($event){
            $history = $eventservice->getHistory($event);
    	}
    	
        $viewmodel->setVariable('history', $history);
    	$viewmodel->setVariable('event', $event);
    	
    	return $viewmodel;
    }
    
    public function addnoteAction(){
        $id = $this->params()->fromQuery('id', null);
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');

        $messages = array();
        
        if($id && $this->getRequest()->isPost()){
            $post = $this->getRequest()->getPost();
            $event = $em->getRepository('Application\Entity\Event')->find($id);
            if($event){
                $eventupdate = new \Application\Entity\EventUpdate();
                $eventupdate->setText($post['new-update']);
                $eventupdate->setEvent($event);
                $em->persist($eventupdate);
                try{
                    $em->flush();
                    $messages['success'][] = "Note correctement ajoutée.";
                } catch (\Exception $ex) {
                    $messages['error'][] = $ex->getMessage();
                }
            } else {
                $messages['error'][] = "Impossible d'ajouter la note (évènement non trouvé).";
            }
        } else {
            $messages['error'][] = "Impossible d'ajouter la note.";
        }
        
        return new JsonModel($messages);
    }
    
    public function savenoteAction(){
        $id = $this->params()->fromQuery('id', null);
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');

        $messages = array();
        
        if($id && $this->getRequest()->isPost()){
            $note = $em->getRepository('Application\Entity\EventUpdate')->find($id);
            $post = $this->getRequest()->getPost();
            if($note){
                $note->setText($post['note']);
                $em->persist($note);
                try{
                    $em->flush();
                    $messages['success'][] = "Note correctement mise à jour.";
                } catch (\Exception $ex) {
                    $messages['error'][] = $ex->getMessage();
                }
            } else {
                $messages['error'][] = "Impossible de mettre à jour la note.";
            }
        }
        return new JsonModel($messages);
    }
    
    public function updatesAction(){
        $id = $this->params()->fromQuery('id', null);

        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');

        $viewmodel = new ViewModel();
    	$request = $this->getRequest();
    	//disable layout if request by Ajax
    	$viewmodel->setTerminal($request->isXmlHttpRequest());
        
        
        $event = $em->getRepository('Application\Entity\Event')->find($id);
        
        $viewmodel->setVariable('updates', $event->getUpdates());
        
        return $viewmodel;
        
    }
}
