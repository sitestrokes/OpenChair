<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Autoresponder Details Settings
|--------------------------------------------------------------------------
|
|
*/
//https://catcheverycallai.com/
$config['aweber']=array(
					'client_id' => '<AWEBER_CLIENT_ID>',
					'BASE_URL' =>'https://api.aweber.com/1.0/',
					'OAUTH_URL'=>'https://auth.aweber.com/oauth2/',
					'TOKEN_URL'=>'https://auth.aweber.com/oauth2/token',
					'code_challenge'=>'<AWEBER_CODE_CHALLENGE>'
					);


$config['webinar']=array(
						'client_id' => '<GOTOWEBINAR_CLIENT_ID>',
						'BASE_URL' =>'https://api.getgo.com/G2W/rest/v2/',
						'OAUTH_URL'=>'https://api.getgo.com/oauth/v2/authorize',
						'TOKEN_URL'=>'https://api.getgo.com/oauth/v2/token',
						'client_secret'=>'<GOTOWEBINAR_CLIENT_SECRET>'
						);