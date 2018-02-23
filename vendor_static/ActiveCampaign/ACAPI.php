<?php

namespace AC;

require_once(dirname(__FILE__)."/lib/includes/ActiveCampaign.class.php");
require_once(dirname(__FILE__)."/Connector.php");

class ACAPI{
    
    private static $connector = [];
    
    public static function start(){
        
        $apiOldURL = 'https://4geeks.api-us1.com';
        $apiURL = 'https://4geeks.api-us1.com/api/3/';
        $apiKey = "30f9f6fe16d0c589445290af8c87fd7658500c700eda21ad8a232103d0037486c57e7a7d";
        
        if(empty(self::$connector['new'])) self::$connector['new'] = new \AC\Connector($apiURL, $apiKey);
        if(empty(self::$connector['old'])) self::$connector['old'] = new \ActiveCampaign($apiOldURL, $apiKey);
        
    }
    
    /**
     *  $contact = array(
    		"email"              => "test@example.com",
    		"first_name"         => "Test",
    		"last_name"          => "Test",
    		"p[{$list_id}]"      => $list_id,
    		"status[{$list_id}]" => 1, // "Active" status
    	);
    */
    
    public static function subscribeToList($contact, $listId, $tags=null){
        
        $contact["p[{$listId}]"] = $listId;
        $contact["status[{$listId}]"] = 1;
        if($tags) $contact["tags"] = $tags;
        
    	$result = self::$connector['old']->api("contact/sync", $contact);
    	if (!(int)$result->success) throw new \Exception('Syncing contact failed. Error returned: '. $result->error);
        
        // successful request
        return (int)$result->subscriber_id;
    }
    
    public static function tagContact($email, $tags){
        
        $request["email"] = $email;
        $request["tags"] = $tags;

    	$result = self::$connector['old']->api("contact/tag_add", $request);
    	if (!(int)$result->success) throw new \Exception('Syncing contact failed. Error returned: '. $result->error);
        
        // successful request
        return (int)$result->subscriber_id;
    }
    
    public static function updateContact($email, $fieldsToUpdate){
        
    	$contact = array_merge($fieldsToUpdate, ["email" => $email]);
    	
    	/*
    	$contact = array(
    		"first_name"         => "Test",
    		"last_name"          => "Test",
    		"p[{$list_id}]"      => $list_id,
    		"status[{$list_id}]" => 1, // "Active" status
    	);*/
    	$result = self::$connector['old']->api("contact/sync", $contact);
    	if (!(int)$result->success) throw new \Exception('Syncing contact failed. Error returned: '. $result->error);
        
        // successful request
        return $result;
    }
    
    public static function getContactByEmail($email){
        
    	$result = self::$connector['old']->api("contact/view?email=".$email);

    	if (!(int)$result->success) throw new \Exception('Error returned: '. $result->error);
        
        // successful request
        return $result;
    }
    
    public static function getAllCustomFields(){
        
        $customFields = self::$connector['old']->api("list/field/view?ids=all");
        //print_r($customFields); die();
        // successful request
        return $customFields;
    }
    
    public static function getCustomField($slug, $listIds='1'){
        
        $slug = strtoupper($slug);
        $args = [];
        $args['ids'] = $listIds;
        $args['global_fields'] = 1;
        $args['full'] = 1;
        
        $list = (array) self::$connector['old']->api("list/list", $args);
        $list = array_shift($list);
        $courseField = self::findField($list->fields,$slug);
        
        if(!$courseField) throw new \Exception('Unable to find custom field: '.$slug);
        return $courseField;
    }
    
    private static function findField($fields, $slug){
        foreach($fields as $f) if($f->perstag == $slug) return $f;
        return null;
    }
}