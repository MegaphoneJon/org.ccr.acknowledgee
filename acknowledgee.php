<?php

require_once 'acknowledgee.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function acknowledgee_civicrm_config(&$config) {
  _acknowledgee_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function acknowledgee_civicrm_xmlMenu(&$files) {
  _acknowledgee_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function acknowledgee_civicrm_install() {
  _acknowledgee_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function acknowledgee_civicrm_uninstall() {
  _acknowledgee_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function acknowledgee_civicrm_enable() {
  _acknowledgee_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function acknowledgee_civicrm_disable() {
  _acknowledgee_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function acknowledgee_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _acknowledgee_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function acknowledgee_civicrm_managed(&$entities) {
  _acknowledgee_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function acknowledgee_civicrm_caseTypes(&$caseTypes) {
  _acknowledgee_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function acknowledgee_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _acknowledgee_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function acknowledgee_civicrm_buildForm( $formName, &$form ) {
  //On a donation page with IHO/IMO add the acknowledgee form fields
  if ($formName == 'CRM_Contribute_Form_Contribution_Main') {
    // Check if we have IHO/IMO enabled on a page.
    if (isset($form->_values['soft_credit_types'])) {
      CRM_Core_Region::instance('contribution-soft-credit-block')->add([
        'template' => 'CRM/Contribute/Form/Contribution/Acknowledgee.tpl',
      ]);
      CRM_Core_Resources::singleton()->addScriptFile('org.ccr.acknowledgee', 'js/acknowledgee.js');
      $form->add('text', 'acknowledgee_first_name', ts('First Name'));
      $form->add('text', 'acknowledgee_last_name', ts('Last Name'));
      $form->add('text', 'acknowledgee_email', ts('Email'));
      $form->add('text', 'acknowledgee_street_address', ts('Street Address'));
      $form->add('text', 'acknowledgee_supplemental_address_1', ts('Street Address 2'));
      $form->add('text', 'acknowledgee_city', ts('City'));
      $country = array(CRM_Core_PseudoConstant::country());
      $form->addElement('select', 'acknowledgee_country_id', ts('Country'), $country[0], array('class' => 'crm-select2'));
      $form->addChainSelect('acknowledgee_state_province_id');
      $form->add('text', 'acknowledgee_postal_code', ts('Postal Code'));
      $defaults['acknowledgee_country_id'] = 1228;
      $form->setDefaults($defaults);
      $form->assign('elements', 
                     array(
                      'acknowledgee_first_name',
                      'acknowledgee_last_name',
                      'acknowledgee_email',
                      'acknowledgee_street_address',
                      'acknowledgee_supplemental_address_1',
                      'acknowledgee_city',
                      'acknowledgee_country_id',
                      'acknowledgee_state_province_id',
                      'acknowledgee_postal_code',
                     )
                   );
    }
  }
}

function acknowledgee_civicrm_postProcess( $formName, &$form ) {
  if ($formName == 'CRM_Contribute_Form_Contribution_Confirm') {
    //Currently only does this for the memorial.  Easy to add Honoree if need be
    if ( isset($form->_values['honor']) && 
         $form->_values['honor']['soft_credit_type'] == 'In Memory of' &&
          !empty($form->_params['acknowledgee_first_name']) &&
          !empty($form->_params['acknowledgee_last_name'])) {

      //Set memorial id
      $memorialee_cid = $form->_values['honor']['honor_id'];

      //Create initial API param arrays for contact, email and address creation
      $acknowledgee_params = array(
        'sequential' => 1,
        'contact_type' => 'Individual'
      );
      $acknowledgee_email_params = array(
        'sequential' => 1,
        'location_type_id' => 1,
      );
      $acknowledgee_address_params = array(
        'sequential' => 1,
        'location_type_id' => 1,
      );

      //Set the param values for contact and address creation
      //count is a bit of hack, but it's simple and should always work in the case
      $acknowledgee_count = 0;
      foreach ($form->_params as $key => $param) {
        if (substr($key, 0, 12) == 'acknowledgee') {
          $acknowledgee_count++;
          if ($acknowledgee_count < 3) {
            $acknowledgee_params[substr($key, 13)] = $param;
          }
          elseif ($acknowledgee_count == 3) {
            $acknowledgee_email_params[substr($key, 13)] = $param;
          }
          else {
            $acknowledgee_address_params[substr($key, 13)] = $param;
          }
        }
      }

      //Create the acknowledgee contact
      $acknowledgee = civicrm_api3('Contact', 'create', $acknowledgee_params);

      //Create the acknowledgee email
      $acknowledgee_cid = $acknowledgee_email_params['contact_id'] = $acknowledgee['id'];
      $acknowledgee_email = civicrm_api3('Email', 'create', $acknowledgee_email_params);

      //Create the acknowledgee address
      $acknowledgee_address_params['contact_id'] = $acknowledgee_cid;
      $acknowledgee_address = civicrm_api3('Address', 'create', $acknowledgee_address_params);

      //Create the relationship between the memorialee and acknowledgee
      $ack_relationship = civicrm_api3('Relationship', 'create', array(
        'sequential' => 1,
        'contact_id_a' => $memorialee_cid,
        'contact_id_b' => $acknowledgee_cid,
        'relationship_type_id' => 27,
      ));
    }
  }
}
