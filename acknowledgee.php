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
      $acknowledgeeProfileId = civicrm_api3('UFGroup', 'getvalue', ['return' => 'id', 'name' => 'acknowledgee']);
      $acknowledgeeProfileFields = CRM_Core_BAO_UFGroup::getFields(
      $acknowledgeeProfileId, FALSE, NULL, NULL, NULL, FALSE, NULL, TRUE, NULL, CRM_Core_Permission::CREATE
      );
      $form->assign('acknowledgeeProfileFields', $acknowledgeeProfileFields);
      // add the form elements
      foreach ($acknowledgeeProfileFields as $name => $field) {
        CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, NULL, FALSE, FALSE, NULL, 'acknowledgee');
      }
    }
  }
}

function acknowledgee_civicrm_postProcess( $formName, &$form ) {
  if ($formName == 'CRM_Contribute_Form_Contribution_Confirm') {
    //Currently only does this for the memorial.  Easy to add Honoree if need be
    if (isset($form->_values['honor']) &&
    $form->_values['honor']['soft_credit_type'] == 'In Memory of' &&
    isset($form->_params['acknowledgee']) &&
    !empty($form->_params['acknowledgee']['first_name']) &&
    !empty($form->_params['acknowledgee']['last_name'])) {

      $acknowledgeeParams = $form->_params['acknowledgee'];
      $fields = CRM_Contact_BAO_Contact::exportableFields('Individual');
      $result = civicrm_api3('Contact', 'duplicatecheck', $acknowledgeeParams);

      $acknowledgeeId = CRM_Contact_BAO_Contact::createProfileContact($acknowledgeeParams, CRM_Core_DAO::$_nullArray);

      // Populate the Acknowledgee field on the contribution
      $acknowledgeeCustomField = getAcknowledgeeCustomField();
      $result = civicrm_api3('Contribution', 'create', [
        'id' => $form->_contributionID,
        $acknowledgeeCustomField => $acknowledgeeId,
      ]);
    }
  }
}

function getAcknowledgeeCustomField() {
  $fieldNumber = civicrm_api3('CustomField', 'get', ['name' => "acknowledgee"])['id'];
  return "custom_$fieldNumber";
}
