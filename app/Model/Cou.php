<?php
/**
 * COmanage Registry COU Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class Cou extends AppModel {
  // Define class name for cake
  public $name = "Cou";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A COU is attached to a CO
    "Co",
    // Also attached to a parent COU
    "ParentCou" => array(
      'className' => 'Cou',
      'foreignKey'=>'parent_id'
    ),
  );
  
  public $hasMany = array(
    "ChildCou" => array(
      'className' => 'Cou',
      'foreignKey'=>'parent_id'
    ),
    // We specifically want to delete CO Groups that reference this COU
    "CoGroup" => array('dependent' => true),
    "CoPersonRole",
    "CoPetition",
    "CoTermsAndConditions",
    "CoEnrollmentFlowAuthzCou" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'authz_cou_id'
    ),
    "CoExpirationPolicyActCou" => array(
      'className' => 'CoExpirationPolicy',
      'foreignKey' => 'act_cou_id'
    ),
    "CoExpirationPolicyCondCou" => array(
      'className' => 'CoExpirationPolicy',
      'foreignKey' => 'cond_cou_id'
    ),
    "CoPipelineSyncCou" => array(
      'className' => 'CoPipeline',
      'foreignKey' => 'sync_cou_id'
    ),
    "CoPipelineReplaceCou" => array(
      'className' => 'CoPipeline',
      'foreignKey' => 'sync_replace_cou_id'
    )
  );

  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
  // This breaks with tree behavior, see https://bugs.internet2.edu/jira/browse/CO-230
  //  public $order = array("Cou.name");
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'name' => array(
      'rule' => array('validateInput',
                      // Colons are not permitted in COU names since they could potentially
                      // cause conflicts with autogenerated CO Group names
                      array('invalidchars' => '<>:')),
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'parent_id' => array(
      // Strangely, when specified is numeric cake decides to make required = true
      'rule' => '/[0-9]*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'lft' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'rght' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );

  public $actsAs = array('Containable',
                         // TreeBehavior requires beforeDelete/afterDelete, which Changelog will interrupt
                         'Tree' => array('priority' => 4),
                         'Changelog' => array('priority' => 5));

  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */
  
  public function afterSave($created, $options = Array()) {
    if($created
       && !empty($this->data['Cou']['id'])
       && !empty($this->data['Cou']['co_id'])) {
      // Run setup for new COU
      
      $this->setup($this->data['Cou']['co_id'], $this->data['Cou']['id']);
    }
    
    return true;
  }
  
  /**
   * Obtain all COUs within a specified CO.
   *
   * @since  COmanage Registry v0.4
   * @param  integer CO ID
   * @param  string Format, one of "names", "ids", or "hash" of id => name
   * @return Array List or hash of member COUs, as specified by $format
   */
  
  public function allCous($coId, $format="hash") {
    $args['conditions']['Cou.co_id'] = $coId;
    $args['order'] = 'Cou.name ASC';
    
    $cous = $this->find("list", $args);
    
    if($cous) {
      switch($format) {
      case 'names':
        return(array_values($cous));
        break;
      case 'ids':
        return(array_keys($cous));
        break;
      default:
        return($cous);
        break;
      }
    }
    
    return(array());
  }

  /**
   * Generates dropdown option list for html for a COU.
   *
   * @since  COmanage Registry v0.3
   * @param  integer COU that needs parent options; NULL if new
   * @param  integer CO ID
   * @return Array Array of [id] => [name]
   */
  
  public function potentialParents($currentCou, $coId) {
    // Editing an existing COU requires removing it and its children from the list of potential parents
    if($currentCou) {
      // Find this COU and its children
      $childrenArrays = $this->children($currentCou, false, 'id');
      $childrenList = Set::extract($childrenArrays, '{n}.Cou.id');
      
      // Set up filter to ignore children
      $conditions = array(
                    'AND' => array(
                      array(
                        'NOT' => array(
                          array('Cou.id' => $childrenList),
                          array('Cou.id' => $currentCou)
                        )
                      ),
                      array(
                        array('Cou.co_id' => $coId)
                      )
                    )
                  );
    } else {
      $conditions = array();
      $conditions['Cou.co_id'] = $coId;
    }

    $args = array();
    $args['conditions'] = $conditions;
    $args['contain'] = false;
    
    $args = array();
    $args['conditions'] = $conditions;
    $args['contain'] = false;
    
    // Create options list all other COUS in CO
    $optionArrays = $this->find('all', $args);
    $optionList = Set::combine($optionArrays, '{n}.Cou.id','{n}.Cou.name');
    
    return $optionList;
  }

  /**
   * Obtain the child COUs of a COU.
   *
   * @since  COmanage Registry v0.3
   * @param  String Name of Parent COU
   * @param  Integer CO ID for Parent COU
   * @param  Boolean Whether or not to return $parentCou in the results
   * @return Array List of COU IDs and Names
   * @throws InvalidArgumentException
   */
  
  public function childCous($parentCou, $co_id, $includeParent=false) {
    // Find $parentCou
    
    $args = array();
    $args['conditions']['Cou.name'] = $parentCou;
    $args['conditions']['Cou.co_id'] = $co_id;
    $args['contain'] = false;
    
    $parent = $this->find('first', $args);
    
    if(!empty($parent['Cou']['id'])) {
      return $this->childCousById($parent['Cou']['id'], $includeParent);
    } else {
      throw new InvalidArgumentException(_txt('er.unknown', array($parentCou)));
    }
  }

  /**
   * Obtain the child COUs of a COU.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $parentCouId   COU ID for Parent COU
   * @param  Boolean $includeParent Whether or not to return $parentCou in the results
   * @return Array List of COU IDs and Names
   * @throws InvalidArgumentException
   */
  
  public function childCousById($parentCouId, $includeParent=false) {
    // Find $parentCou
    
    $args = array();
    $args['conditions']['Cou.id'] = $parentCouId;
    $args['contain'] = false;
    
    $parent = $this->find('first', $args);
    
    if(!isset($parent['Cou']['id'])) {
      throw new InvalidArgumentException(_txt('er.unknown', array($parentCouId)));
    }
    
    $children = $this->children($parentCouId,
                                false,
                                array('id', 'name'));
    
    $ret = array();
    
    if($includeParent) {
      $ret[ $parent['Cou']['id'] ] = $parent['Cou']['name'];
    }
    
    foreach($children as $child) {
      $ret[ $child['Cou']['id'] ] = $child['Cou']['name'];
    }
    
    return $ret;
  }

  /**
   * Check if couId is a member of the current CO.
   *
   * @since  COmanage Registry v0.3
   * @param  integer COU ID to check
   * @param  integer CO ID
   * @return boolean True if member, false otherwise
   */
  
  public function isInCo($couId, $coId) {
    $args = array();
    $args['conditions']['Cou.id'] = $couId;
    $args['contain'] = false;
  
    $couData = $this->find('first', $args);
  
    if(!empty($couData['Cou']['co_id'])
        && $couData['Cou']['co_id'] == $coId) {
          return true;
        }
        return false;
  }

  /**
   * Check if couNode is a child of couBranch.
   *
   * @since  COmanage Registry v0.3
   * @param  integer Head of the branch to be searched
   * @param  integer Node to be looked for
   * @return boolean True if child, false otherwise
   */

  public function isChildCou($couBranch, $couNode) {
    // Get list of all children of $couBranch
    $childrenArrays = $this->children($couBranch, false, 'id');
    $childrenList = Set::extract($childrenArrays, '{n}.Cou.id');

    // Check for NULL to avoid warning/error from array_search (See CO-240)
    if(($childrenList != NULL)
      && (array_search($couNode, $childrenList) !== false)) {
        // Node was found in the branch
        return true;
    }
    return false;
  }
  
  /**
   * Perform initial setup for a COU.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer CO ID
   * @param  Integer COU ID
   * @return Boolean True on success
   */
  
  public function setup($coId, $couId) {
    // Add default groups
    $this->CoGroup->addDefaults($coId, $couId);
    
    return true;
  }
}
