<?php
/**
 * COmanage Registry Service Eligibility Settings Controller
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class ServiceEligibilitySettingsController extends StandardController {
  // Class name, used by Cake
  public $name = "ServiceEligibilitySettings";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_id' => 'asc'
    )
  );

  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Render Service Eligibility Settings for this CO.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function index() {
    // This operates a bit like CO Settings... basically we insert a row for
    // this CO if there isn't one, then we redirect to edit for that row.
    
    $settingId = $this->ServiceEligibilitySetting->field('id', array('ServiceEligibilitySetting.co_id' => $this->cur_co['Co']['id']));
    
    if(!$settingId) {
      // Create the row
      
      $settings = array(
        'co_id'             => $this->cur_co['Co']['id'],
        'allow_multiple'    => true,
        'require_selection' => false
      );
      
      $this->ServiceEligibilitySetting->clear();
      $this->ServiceEligibilitySetting->save($settings);
      
      $settingId = $this->ServiceEligibilitySetting->id;
    }
    
    $this->redirect(
      array(
        'action' => 'edit',
        $settingId
      )
    );
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Edit an existing Service Eligibility Setting?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Service Eligibility Setting?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Service Eligibility Setting?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
