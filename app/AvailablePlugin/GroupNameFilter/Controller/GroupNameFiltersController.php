<?php
/**
 * COmanage Registry Group Name Data Filter Controller
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class GroupNameFiltersController extends StandardController {
  // Class name, used by Cake
  public $name = "GroupNameFilters";
  
  public $requires_co = true;
  
  // Establish pagination parameters for HTML views
/*
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'filepath' => 'asc'
    )
  );*/
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v3.3.0
   */

  function beforeRender() {
    // Pull the list of identifier types
    $this->set('vv_identifier_types', $this->GroupNameFilter->DataFilter->Co->CoGroup->Identifier->types($this->cur_co['Co']['id'], 'type'));
    
    parent::beforeRender();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Delete an existing Group Name Filter?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Group Name Filter?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Group Name Filter?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing Group Name Filter?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3.0
   */

  public function performRedirect() {
    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "data_filters";
    $target['action'] = 'index';
    $target['co'] = $this->cur_co['Co']['id'];

    $this->redirect($target);
  }
}
