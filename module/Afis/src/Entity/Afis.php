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
namespace Afis\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zend\Form\Annotation;
use Application\Entity\TemporaryResource;
use Application\Entity\Organisation;
/**
 * @ORM\Entity
 * @ORM\Table(name="afis")
 *
 * @author Loïc Perrin
 *        
 */
class Afis extends TemporaryResource
{
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @Annotation\Type("Zend\Form\Element\Hidden")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string")
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Options({"label":"Nom :"})
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Options({"label":"Nom abrégé :"})
     */
    protected $shortname;
    /**
     * @ORM\ManyToOne(targetEntity="Application\Entity\Organisation")
     * @ORM\JoinColumn(nullable=false)
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Options({"label":"Organisation :", "empty_option":"Choisir l'organisation"})
     */
    protected $organisation;
    
    /**
     * 
     * @ORM\Column(name="state", type="boolean", options={"default" = 1})
     * @Annotation\Type("Zend\Form\Element\Hidden")
     * @Annotation\Options({"label":"Hors service :"})
     */
    protected $state = 1;
    
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getShortname()
    {
        return $this->shortname;
    }

    public function setShortname($name)
    {
        $this->shortname = $name;
    }
    
    public function setOrganisation(Organisation $organisation)
    {
        $this->organisation = $organisation;
    }

    public function getOrganisation()
    {
        return $this->organisation;
    }
    
    public function getState() {
        return $this->state;
    }
    
    public function setState($state) {
        $this->state = $state;
    }
    
    public function getArrayCopy()
    {
        $object_vars = get_object_vars($this);
        $object_vars['organisation'] = $this->organisation->getId();
        return $object_vars;
    }
    
    public function setValues($values, $em)
    {
        /*
        * A REVOIR, PAS TRES ELEGANT...
        */
        foreach ($values->getElements() as $element) 
        {
            if($element->getValue()) {
                $name = $element->getAttributes()['name'];
                $value = $element->getValue();
                
                ($name == 'organisation') ? $value = $em->getRepository(Organisation::class)->find($value) : null;
                ($name == 'decommissionned') ? $value = (bool) $value : null;
                
                if($name !== 'submit' && $name !== 'id')
                {
                    $set = 'set'.ucfirst($name);
                    $this->{$set}($value);
                }
            }
        } 
    }
}