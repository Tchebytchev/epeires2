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
namespace Application\Factories;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Application\Entity\RadarCategory;
use Application\Entity\CustomField;
use Application\Entity\AntennaCategory;
use Application\Entity\FrequencyCategory;
use Application\Entity\BrouillageCategory;
use Application\Entity\MilCategory;

/**
 *
 * @author Bruno Spyckerelle
 *        
 */
class CategoryEntityFactory implements ServiceLocatorAwareInterface
{

    private $sm;

    private $em = null;

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->sm = $serviceLocator;
    }

    public function getServiceLocator()
    {
        return $this->sm;
    }

    private function getEntityManager()
    {
        if (! $this->em) {
            $this->em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }
        return $this->em;
    }

    public function createMilCategory()
    {
        $em = $this->getEntityManager();
        
        $stringtype = $em->getRepository('Application\Entity\CustomFieldType')->findOneBy(array(
            'type' => 'string'
        ));
        
        $milcat = new MilCategory();
        $namefield = new CustomField();
        $namefield->setCategory($milcat);
        $namefield->setName('Nom');
        $namefield->setType($stringtype);
        $namefield->setPlace(1);
        $namefield->setDefaultValue("");
        $namefield->setTooltip("");
        $milcat->setFieldname($namefield);
        
        $lower = new CustomField();
        $lower->setCategory($milcat);
        $lower->setName('Plancher');
        $lower->setType($stringtype);
        $lower->setPlace(2);
        $lower->setDefaultValue("");
        $lower->setTooltip("");
        $milcat->setLowerLevelField($lower);
        
        $upper = new CustomField();
        $upper->setCategory($milcat);
        $upper->setName("Plafond");
        $upper->setType($stringtype);
        $upper->setPlace(3);
        $upper->setDefaultValue("");
        $upper->setTooltip("");
        $milcat->setUpperLevelField($upper);
        
        $milcat->setZonesRegex('');
        
        $em->persist($upper);
        $em->persist($lower);
        $em->persist($namefield);
        return $milcat;
    }

    /**
     * To be persisted
     * 
     * @return \Application\Entity\RadarCategory
     */
    public function createRadarCategory()
    {
        $em = $this->getEntityManager();
        $radarcat = new RadarCategory();
        $radarfield = new CustomField();
        $radarfield->setCategory($radarcat);
        $radarfield->setName('Radar');
        $radarfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'radar'
        )));
        $radarfield->setPlace(1);
        $radarfield->setDefaultValue("");
        $radarfield->setTooltip("");
        $statusfield = new CustomField();
        $statusfield->setCategory($radarcat);
        $statusfield->setName('Indisponible');
        $statusfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'boolean'
        )));
        $statusfield->setPlace(2);
        $statusfield->setDefaultValue("");
        $statusfield->setTooltip("");
        $radarcat->setFieldname($radarfield);
        $radarcat->setRadarfield($radarfield);
        $radarcat->setStatefield($statusfield);
        
        // si aucune cat par défaut --> nouvelle catégorie par défaut
        $cats = $em->getRepository('Application\Entity\RadarCategory')->findBy(array(
            'defaultradarcategory' => true
        ));
        $radarcat->setDefaultRadarCategory((count($cats) == 0));
        
        $em->persist($radarfield);
        $em->persist($statusfield);
        return $radarcat;
    }

    public function createAntennaCategory()
    {
        $em = $this->getEntityManager();
        $antennacat = new AntennaCategory();
        $antennafield = new CustomField();
        $antennafield->setCategory($antennacat);
        $antennafield->setName('Antenne');
        $antennafield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'antenna'
        )));
        $antennafield->setPlace(1);
        $antennafield->setDefaultValue("");
        $antennafield->setTooltip("");
        $statusfield = new CustomField();
        $statusfield->setCategory($antennacat);
        $statusfield->setName('Indisponible');
        $statusfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'boolean'
        )));
        $statusfield->setPlace(2);
        $statusfield->setDefaultValue("");
        $statusfield->setTooltip("");
        
        $frequenciesfield = new CustomField();
        $frequenciesfield->setCategory($antennacat);
        $frequenciesfield->setName('Fréquences impactées');
        $frequenciesfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'frequency'
        )));
        $frequenciesfield->setPlace(3);
        $frequenciesfield->setDefaultValue("");
        $frequenciesfield->setMultiple(true);
        $frequenciesfield->setTooltip("Pas de valeur = toutes.");
        
        $antennacat->setFrequenciesField($frequenciesfield);
        $antennacat->setFieldname($antennafield);
        $antennacat->setAntennafield($antennafield);
        $antennacat->setStatefield($statusfield);
        $em->persist($antennafield);
        $em->persist($statusfield);
        $em->persist($frequenciesfield);
        // si aucune cat par défaut --> nouvelle catégorie par défaut
        $cats = $em->getRepository('Application\Entity\AntennaCategory')->findBy(array(
            'defaultantennacategory' => true
        ));
        $antennacat->setDefaultAntennaCategory((count($cats) == 0));
        
        return $antennacat;
    }

    public function createFrequencyCategory()
    {
        $em = $this->getEntityManager();
        $frequencycat = new FrequencyCategory();
        $frequencyfield = new CustomField();
        $frequencyfield->setCategory($frequencycat);
        $frequencyfield->setName('Fréquence');
        $frequencyfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'frequency'
        )));
        $frequencyfield->setPlace(1);
        $frequencyfield->setDefaultValue("");
        $frequencyfield->setTooltip("");
        $statefield = new CustomField();
        $statefield->setCategory($frequencycat);
        $statefield->setName('Indisponible');
        $statefield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'boolean'
        )));
        $statefield->setPlace(2);
        $statefield->setDefaultValue("");
        $statefield->setTooltip("");
        
        $currentAntenna = new CustomField();
        $currentAntenna->setCategory($frequencycat);
        $currentAntenna->setName('Couverture');
        $currentAntenna->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'select'
        )));
        $currentAntenna->setPlace(3);
        $currentAntenna->setDefaultValue("Normale\nSecours");
        $currentAntenna->setTooltip("");
        $otherfreq = new CustomField();
        $otherfreq->setCategory($frequencycat);
        $otherfreq->setName('Utiliser fréquence');
        $otherfreq->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'frequency'
        )));
        $otherfreq->setPlace(4);
        $otherfreq->setDefaultValue("");
        $otherfreq->setTooltip("");
        
        
        $cause = new CustomField();
        $cause->setCategory($frequencycat);
        $cause->setName("Cause");
        $cause->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
                'type' => 'text'
            )));
        $cause->setPlace(5);
        $cause->setDefaultValue("");
        $cause->setTooltip("");
        
        $frequencycat->setFieldname($frequencyfield);
        $frequencycat->setFrequencyField($frequencyfield);
        $frequencycat->setCurrentAntennaField($currentAntenna);
        $frequencycat->setStateField($statefield);
        $frequencycat->setOtherFrequencyField($otherfreq);
        $frequencycat->setCauseField($cause);
        
        $em->persist($frequencyfield);
        $em->persist($statefield);
        $em->persist($currentAntenna);
        $em->persist($otherfreq);
        $em->persist($cause);
        
        // si aucune cat par défaut --> nouvelle catégorie par défaut
        $cats = $em->getRepository('Application\Entity\FrequencyCategory')->findBy(array(
            'defaultfrequencycategory' => true
        ));
        $frequencycat->setDefaultFrequencyCategory((count($cats) == 0));
        
        return $frequencycat;
    }

    public function createBrouillageCategory()
    {
        $em = $this->getEntityManager();
        $brouillagecat = new BrouillageCategory();
        $frequencyfield = new CustomField();
        $frequencyfield->setCategory($brouillagecat);
        $frequencyfield->setName('Fréquence');
        $frequencyfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'frequency'
        )));
        $frequencyfield->setPlace(1);
        $frequencyfield->setDefaultValue("");
        $frequencyfield->setTooltip("");
        
        $levelfield = new CustomField();
        $levelfield->setCategory($brouillagecat);
        $levelfield->setName('Niveau');
        $levelfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'string'
        )));
        $levelfield->setPlace(2);
        $levelfield->setDefaultValue("");
        $levelfield->setTooltip("");
        
        $rnavfield = new CustomField();
        $rnavfield->setCategory($brouillagecat);
        $rnavfield->setName('Balise');
        $rnavfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'string'
        )));
        $rnavfield->setPlace(3);
        $rnavfield->setDefaultValue("");
        $rnavfield->setTooltip("");
        
        $distancefield = new CustomField();
        $distancefield->setCategory($brouillagecat);
        $distancefield->setName('Distance');
        $distancefield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'string'
        )));
        $distancefield->setPlace(4);
        $distancefield->setDefaultValue("");
        $distancefield->setTooltip("");
        
        $azimutfield = new CustomField();
        $azimutfield->setCategory($brouillagecat);
        $azimutfield->setName('Azimut');
        $azimutfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'string'
        )));
        $azimutfield->setPlace(5);
        $azimutfield->setDefaultValue("");
        $azimutfield->setTooltip("");
        
        $originfield = new CustomField();
        $originfield->setCategory($brouillagecat);
        $originfield->setName('Plaignant');
        $originfield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'select'
        )));
        $originfield->setPlace(6);
        $originfield->setDefaultValue("Sol\nBord\nSol+Bord");
        $originfield->setTooltip("");
        
        $typefield = new CustomField();
        $typefield->setCategory($brouillagecat);
        $typefield->setName('Type de bruit');
        $typefield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'select'
        )));
        $typefield->setPlace(7);
        $typefield->setDefaultValue("Brouillage\nInterférence");
        $typefield->setTooltip("");
        
        $causebrouillagefield = new CustomField();
        $causebrouillagefield->setCategory($brouillagecat);
        $causebrouillagefield->setName('Cause du brouillage');
        $causebrouillagefield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'select'
        )));
        $causebrouillagefield->setPlace(8);
        $causebrouillagefield->setDefaultValue("Radio FM\nBruit industriel\nAutre\nRien");
        $causebrouillagefield->setTooltip("");
        
        $causeinterferencefield = new CustomField();
        $causeinterferencefield->setCategory($brouillagecat);
        $causeinterferencefield->setName('Cause interférence');
        $causeinterferencefield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'select'
        )));
        $causeinterferencefield->setPlace(10);
        $causeinterferencefield->setDefaultValue("Porteuse\nÉmission permanente\nAutre fréquence");
        $causeinterferencefield->setTooltip("");
        
        $commentairebrouillagefield = new CustomField();
        $commentairebrouillagefield->setCategory($brouillagecat);
        $commentairebrouillagefield->setName('Cause du brouillage (commentaire)');
        $commentairebrouillagefield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'text'
        )));
        $commentairebrouillagefield->setPlace(9);
        $commentairebrouillagefield->setDefaultValue("");
        $commentairebrouillagefield->setTooltip("");
        
        $commentaireinterferencefield = new CustomField();
        $commentaireinterferencefield->setCategory($brouillagecat);
        $commentaireinterferencefield->setName('Cause interférence (commentaire)');
        $commentaireinterferencefield->setType($em->getRepository('Application\Entity\CustomFieldType')
            ->findOneBy(array(
            'type' => 'text'
        )));
        $commentaireinterferencefield->setPlace(11);
        $commentaireinterferencefield->setDefaultValue("");
        $commentaireinterferencefield->setTooltip("");
        
        $brouillagecat->setFieldname($frequencyfield);
        $brouillagecat->setFrequencyfield($frequencyfield);
        $brouillagecat->setLevelField($levelfield);
        $brouillagecat->setRnavField($rnavfield);
        $brouillagecat->setDistanceField($distancefield);
        $brouillagecat->setAzimutField($azimutfield);
        $brouillagecat->setOriginField($originfield);
        $brouillagecat->setTypeField($typefield);
        $brouillagecat->setCauseBrouillageField($causebrouillagefield);
        $brouillagecat->setCauseInterferenceField($causeinterferencefield);
        $brouillagecat->setCommentaireBrouillageField($commentairebrouillagefield);
        $brouillagecat->setCommentaireInterferenceField($commentaireinterferencefield);
        
        $em->persist($frequencyfield);
        $em->persist($levelfield);
        $em->persist($rnavfield);
        $em->persist($distancefield);
        $em->persist($azimutfield);
        $em->persist($originfield);
        $em->persist($typefield);
        $em->persist($causebrouillagefield);
        $em->persist($causeinterferencefield);
        $em->persist($commentairebrouillagefield);
        $em->persist($commentaireinterferencefield);
        
        // si aucune cat par défaut --> nouvelle catégorie par défaut
        $cats = $em->getRepository('Application\Entity\BrouillageCategory')->findBy(array(
            'defaultbrouillagecategory' => true
        ));
        $brouillagecat->setDefaultBrouillageCategory((count($cats) == 0));
        
        return $brouillagecat;
    }
}