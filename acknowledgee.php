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
  $entities[] = [
    'module' => 'org.ccr.acknowledgee',
    'name' => 'acknowledgee_relationship_type',
    'entity' => 'RelationshipType',
    'params' => [
      'version' => 3,
      'name_a_b' => 'is Memorial Acknowledgee of',
      'label_a_b' => 'is Memorial Acknowledgee of',
      'name_b_a' => 'Memorial Acknowledgee is',
      'label_b_a' => 'Memorial Acknowledgee is',
      'description' => 'Provided by Acknowledgee extension',
      'contact_type_a' => NULL,
      'contact_type_b' => NULL,
      'is_reserved' => 1,
      'is_active' => 1,
    ],
  ];
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
    // Check if we have IHO/IMO enabled on a page.  If so, add the "acknowledgee"
    // block and the JS to support it.
    if (isset($form->_values['soft_credit_types'])) {
      CRM_Core_Region::instance('contribution-soft-credit-block')->add([
        'template' => 'CRM/Contribute/Form/Contribution/Acknowledgee.tpl',
      ]);
      CRM_Core_Resources::singleton()->addScriptFile('org.ccr.acknowledgee', 'js/acknowledgee.js');
      $acknowledgeeProfileId = civicrm_api3('UFGroup', 'getvalue', ['return' => 'id', 'name' => 'acknowledgee']);
      $acknowledgeeProfileFields = CRM_Core_BAO_UFGroup::getFields(
      $acknowledgeeProfileId, FALSE, NULL, NULL, NULL, FALSE, NULL, TRUE, NULL, CRM_Core_Permission::CREATE
      );
      /* Ugh - adding an additional profile to the Contribution form results in that field's data being used for
       * deduping purposes - thanks to CRM_Dedupe_Finder::formatParams() leading with a "flatten" method.
       * So before that happens, we're gonna obfuscate these form names.
       */
      foreach ($acknowledgeeProfileFields as $key => $value) {
        $obfuscated[$key . '-obfuscated'] = $value;
        $obfuscated[$key . '-obfuscated']['name'] = $key . '-obfuscated';
      }
      $form->assign('acknowledgeeProfileFields', $obfuscated);
      // add the form elements
      foreach ($obfuscated as $field) {
        CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, NULL, FALSE, FALSE, NULL, 'acknowledgee');
      }
    }
  }
}

function acknowledgee_civicrm_postProcess($formName, &$form) {

  if ($formName == 'CRM_Contribute_Form_Contribution_Confirm') {
    //Currently only does this for the memorial.  Easy to add Honoree if need be.
    if (isset($form->_values['honor']) &&
    $form->_values['honor']['soft_credit_type'] == 'In Memory of' &&
    isset($form->_params['acknowledgee']) &&
    !empty($form->_params['acknowledgee']['first_name-obfuscated']) &&
    !empty($form->_params['acknowledgee']['last_name-obfuscated'])) {

      // De-obfuscate the fields.
      foreach ($form->_params['acknowledgee'] as $obfuscatedKey => $value) {
        $deobfuscatedKey = substr($obfuscatedKey, 0, -11);
        $deobfuscatedFields[$deobfuscatedKey] = $value;
      }
      // First, check if this is a duplicate of a record already in the database.
      $acknowledgeeParams['match'] = $deobfuscatedFields;
      $acknowledgeeParams['match']['contact_type'] = 'Individual';
      $duplicateIds = civicrm_api3('Contact', 'duplicatecheck', $acknowledgeeParams);
      $acknowledgeeId = NULL;
      if ($duplicateIds['count']) {
        reset($duplicateIds['values']);
        $acknowledgeeId = key($duplicateIds['values']);
      }
      $acknowledgeeId = CRM_Contact_BAO_Contact::createProfileContact($acknowledgeeParams['match'], CRM_Core_DAO::$_nullArray, $acknowledgeeId);

      // Populate the Acknowledgee field on the contribution
      $acknowledgeeCustomField = getAcknowledgeeCustomField();
      civicrm_api3('Contribution', 'create', [
        'id' => $form->_contributionID,
        $acknowledgeeCustomField => $acknowledgeeId,
      ]);
      // Create a relationship between acknowledgee an memorialee.
      $memorialeeId = $form->_values['honor']['honor_id'];
      $relationshipTypeId = civicrm_api3('RelationshipType', 'get', ['name_a_b' => 'is Memorial Acknowledgee of'])['id'];
      if ($memorialeeId && $acknowledgeeId && $relationshipTypeId) {
        civicrm_api3('Relationship', 'create', [
          'contact_id_a' => $acknowledgeeId,
          'contact_id_b' => $memorialeeId,
          'relationship_type_id' => $relationshipTypeId,
        ]);
      }
    }
  }
}

function getAcknowledgeeCustomField() {
  $fieldNumber = civicrm_api3('CustomField', 'get', ['name' => "acknowledgee"])['id'];
  return "custom_$fieldNumber";
}

function acknowledgee_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  // You can't acknowledge someone without basic contact info.
  if ($formName == 'CRM_Contribute_Form_Contribution_Main' &&
  isset($fields['acknowledgee']) &&
  ($fields['acknowledgee']['first_name'] || $fields['acknowledgee']['last_name'])) {

    // Is there a first AND last name for acknowledgee?
    if (!($fields['acknowledgee']['first_name'] && $fields['acknowledgee']['last_name'])) {
      //What ugly syntax, but oh well.
      $errors['acknowledgee[first_name]'] = ts('Acknowledgees must have both a first and last name.');
    }
    // Detect email fields.
    $hasEmail = FALSE;
    foreach ($fields['acknowledgee'] as $key => $value) {
      if (substr($key, 0, 5) == 'email') {
        // Account for multiple email fields.
        if ($value) {
          $hasEmail = TRUE;
        }
        // We're not saying there IS an error, but if there is, it goes here.
        $emailErrorField = "acknowledgee[$key]";
      }
    }
    // Detect address fields.
    // Uncovered edge case: You have multiple addresses and someone fills in partial info for each.  Not realistic.
    foreach ($fields['acknowledgee'] as $key => $value) {
      if (substr($key, 0, 14) == 'street_address' && $value) {
        $hasStreet = TRUE;
        // We're not saying there IS an error, but if there is, it goes here.
        $addressErrorField = "acknowledgee[$key]";
      }
      if (substr($key, 0, 4) == 'city' && $value) {
        $hasCity = TRUE;
      }
      if (substr($key, 0, 14) == 'state_province' && $value) {
        $hasState = TRUE;
      }
      if (substr($key, 0, 7) == 'country' && $value) {
        $hasCountry = TRUE;
      }
      if (substr($key, 0, 11) == 'postal_code' && $value) {
        $hasPostalCode = TRUE;
      }
    }
    $hasFullAddress = $hasStreet && $hasCity && $hasState && $hasCountry && $hasPostalCode;
    if (!($hasEmail || $hasFullAddress)) {
      $errors[$emailErrorField] = $errors[$addressErrorField] = ts('We need an email or postal address to send our acknowledgment.');
    }
  }
}
