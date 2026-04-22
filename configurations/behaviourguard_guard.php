<?php

// SAFE: Define API base if not already defined
if(!defined('BG_API_BASE')) {
    define("BG_API_BASE", "http://127.0.0.1:8000");
}

function behaviourguard_is_restricted($user_id, $type){

    // Use centralized API base
    $url = BG_API_BASE . "/behavior/check_restriction?user_id=" . intval($user_id) . "&type=" . urlencode($type);

    // Safe timeout so OSSN does not hang if API is down
    $options = [
        "http" => [
            "method" => "GET",
            "timeout" => 2
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if($response === FALSE){
        return false;
    }

    $data = json_decode($response, true);

    if(isset($data["restricted"]) && $data["restricted"] === true){
        return true;
    }

    return false;
}