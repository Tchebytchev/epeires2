<?php

/**
 * Epeires 2
 * @license   https://www.gnu.org/licenses/agpl-3.0.html Affero Gnu Public License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * 
 * @author Bruno Spyckerelle
 *
 */
abstract class FormController extends AbstractActionController {

	protected function processFormMessages($messages, &$json = null){
		if(!isset($json['error'])){
			$json['error'] = array();
		}
		foreach($messages as $key => $message){
			foreach($message as $mkey => $mvalue){//les messages sont de la forme 'type_message' => 'message'
				if(is_array($mvalue)){
					foreach ($mvalue as $nkey => $nvalue){//les fieldsets sont un niveau en dessous
						if($json){
							$n = isset($json['error']) ? count($json['error']) : 0;
							$json['error'][$n] = "Champ ".addslashes($mkey)." incorrect : ".addslashes($nvalue);
						} else {
							$this->flashMessenger()->addErrorMessage(
									"Champ ".addslashes($mkey)." incorrect : ".addslashes($nvalue));
						}
					}
				} else {
					if($json){
						$n = isset($json['error']) ? count($json['error']) : 0;
						$json['error'][$n] = "Champ ".addslashes($key)." incorrect : ".addslashes($mvalue);
					} else {
						$this->flashMessenger()->addErrorMessage(
								"Champ ".addslashes($key)." incorrect : ".addslashes($mvalue));
					}
				}
			}
		}
	}
	
}