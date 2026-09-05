<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  Ion Auth Lang - English
*
* Author: Ben Edmunds
*         ben.edmunds@gmail.com
*         @benedmunds
*
* Location: http://github.com/benedmunds/ion_auth/
*
* Created:  03.14.2010
*
* Description:  English language file for Ion Auth messages and errors
*
*/
// New Pages Word
$lang['site_title'] = 'Agentic Agency';
$lang['dash_page_title']          = 'Dashboard';
$lang['search_page_title']          = 'Search';
$lang['user_page_title']          = 'Users';

// controllers/users.php -> create()

$lang['users_create_error1_heading'] = "Ouch! Something went wrong:";
$lang['users_create_error1_message'] = "Something went wrong when trying to create the new account, please see the errors below:<br><br>";

$lang['users_create_error2_heading'] = "Ouch! Something went wrong:";
$lang['users_create_error2_message'] = "The email address you're trying to use is already used by different account. Please choose a different email address";

$lang['users_create_success_heading'] = "Hooray!";
$lang['users_create_success_message'] = "The new account was created successfully!";

// Add User Modal Form label
$lang['user_first_name']='First Name';
$lang['user_last_name']='Last Name';
$lang['user_email']='Email';
$lang['user_access']='Access Type';
$lang['user_password']='Password';
$lang['user_add_user_btn_text']='Add User';
$lang['user_update_btn_text']='Update';
$lang['user_cancel_btn_text']='Cancel';

// controllers/user.php->EditUser() Edit User functions

$lang['users_userAjax_error1_heading'] = "Ouch! Something went wrong:";
$lang['users_userAjax_error1_Message'] = "User is not Exist";
$lang['users_userAjax_error2_heading'] = "Ouch! Something went wrong:";
$lang['users_userAjax_error2_Message'] = "User id is not valid";

// controllers/users.php -> update()
$lang['users_update_error1_heading'] = "Ouch! Something went wrong:";
$lang['users_update_error1_message'] = "Something went wrong when trying to cerate the new account, please see the errors below:<br><br>";

$lang['users_update_error2_heading'] = "Ouch! Something went wrong:";
$lang['users_update_error2_message'] = "The email address you're trying to use is already used by different account. Please choose a different email address";

$lang['users_update_success_heading'] = "Hooray!";
$lang['users_update_success_message'] = "The account was updated successfully!";

$lang['users_modal_newuser_loadertext'] = "Updating....";
?>
