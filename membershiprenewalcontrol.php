<?php

require_once 'membershiprenewalcontrol.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function membershiprenewalcontrol_civicrm_config(&$config) {
  _membershiprenewalcontrol_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function membershiprenewalcontrol_civicrm_xmlMenu(&$files) {
  _membershiprenewalcontrol_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function membershiprenewalcontrol_civicrm_install() {
  _membershiprenewalcontrol_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function membershiprenewalcontrol_civicrm_uninstall() {
  _membershiprenewalcontrol_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function membershiprenewalcontrol_civicrm_enable() {
  _membershiprenewalcontrol_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function membershiprenewalcontrol_civicrm_disable() {
  _membershiprenewalcontrol_civix_civicrm_disable();
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
function membershiprenewalcontrol_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _membershiprenewalcontrol_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function membershiprenewalcontrol_civicrm_managed(&$entities) {
  _membershiprenewalcontrol_civix_civicrm_managed($entities);
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
function membershiprenewalcontrol_civicrm_caseTypes(&$caseTypes) {
  _membershiprenewalcontrol_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function membershiprenewalcontrol_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _membershiprenewalcontrol_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_pre().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pre
 */
function membershiprenewalcontrol_civicrm_pre($op, $objectName, &$id, &$params) {
  if ($objectName == 'Membership' && $op == 'edit') {
    $existingMembership = civicrm_api3('membership', 'getsingle', array('id' => $id, 'return' => array('status_id', 'end_date', 'is_override', 'membership_type_id')));
    $membershipStatus = civicrm_api3('MembershipStatus', 'get', array(
      'sequential' => 1,
      'return' => array("name"),
    ));

    // Expired, Cancelled, Resigned, Moved interstate
    // Suspended", "Application Rejected", "Member Expelled", and "Application Withdrawn"
    //~ $expiredStatusID = 4;

    //~ $nonRenewableStatuses = array($expiredStatusID, 6 , 10, 12, 14, 15, 16, 17);

    $nonRenewableStatuses = array();

    foreach ($membershipStatus['values'] as $key => $status) {
      if (in_array($status['name'],
        array('Expired', 'Cancelled', 'Resigned', 'Moved interstate', 'Suspended', 'Application Rejected', 'Member Expelled', 'Application Withdrawn'))
        ) {
        $nonRenewableStatuses[] = $status['id'];
      }
    }

    if (in_array($existingMembership['status_id'], $nonRenewableStatuses) && !empty($params['end_date'])
      && strtotime($params['end_date']) > strtotime($existingMembership['end_date'])
    ) {
      // Special condition for QLD. See Redmine 9588.
      if ($existingMembership['status_id'] == 4 && $existingMembership['membership_type_id'] == 35
        && strtotime($existingMembership['end_date']) > strtotime('1 year ago')) {
        return;
      }
      $newStatus = civicrm_api3('membership_status', 'getvalue', array('name'=> 'new', 'return' => 'id'));
      unset($params['id'], $params['membership_id']);
      $id = NULL;
      $params['join_date'] = $params['membership_start_date'] = $params['start_date'];
      $params['status_id'] = $newStatus;
    }
  }
}
