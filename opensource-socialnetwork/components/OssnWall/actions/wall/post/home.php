<?php


/**
 * Open Source Social Network
 *
 * @package   Open Source Social Network (OSSN)
 * @author    OSSN Core Team <info@openteknik.com>
 * @copyright (C) OpenTeknik LLC
 * @license   Open Source Social Network License (OSSN LICENSE)  http://www.opensource-socialnetwork.org/licence
 * @link      https://www.opensource-socialnetwork.org/
 */



require_once ossn_route()->www . 'moderation_api.php';

// init ossnwall
$OssnWall = new OssnWall;

// poster guid and owner guid
$OssnWall->owner_guid  = ossn_loggedin_user()->guid;
$OssnWall->poster_guid = ossn_loggedin_user()->guid;

// check if owner guid is zero then exit
if($OssnWall->owner_guid == 0 || $OssnWall->poster_guid == 0){
    ossn_trigger_message(ossn_print('post:create:error'),'error');
    redirect(REF);
}

// inputs
$post     = input('post');
$friends  = input('friends');
$location = input('location');
$privacy  = input('privacy');

// privacy validation
$privacy = ossn_access_id_str($privacy);
$access  = '';
if(!empty($privacy)){
    $access = input('privacy');
}

// CREATE POST
if($OssnWall->Post($post,$friends,$location,$access)){

    $guid = $OssnWall->getObjectId();
    $post_deleted = false;

    try{

        $image_path = null;
        $result = null;

        if(isset($OssnWall->OssnFile) &&
           isset($OssnWall->OssnFile->dir) &&
           isset($OssnWall->OssnFile->newfilename)){

            $image_path = $OssnWall->OssnFile->dir . $OssnWall->OssnFile->newfilename;
        }

        // IMAGE POST
        if(!empty($image_path)){
            $result = moderate_meme_api($image_path,$post);
        }
        // TEXT ONLY POST
        else if(!empty($post)){
            $result = moderate_comment_api($post);
        }

        if(isset($result['ok']) && $result['ok']){

            $keywords = "";

            if(isset($result['evidence']['keywords_detected']) &&
               count($result['evidence']['keywords_detected']) > 0){
                $keywords = implode(", ", $result['evidence']['keywords_detected']);
            }

            $post_obj = ossn_get_object($guid);

            // DELETE CASE
            if($result['recommended_action'] === "DELETE"){

                $OssnWall->deletePost($guid);
                $post_deleted = true;

                $keyword_block = "";
                if(!empty($keywords)){
                    $keyword_block = "
                    <div style='margin-top:12px;font-size:14px'>
                    <b>Detected abusive terms:</b><br>
                    <span style='color:#c0392b;font-weight:bold'>{$keywords}</span>
                    </div>";
                }

                $message = "
                <div style='
                    background:#fff9db;
                    border:1px solid #f2c94c;
                    border-left:6px solid #f39c12;
                    padding:22px;
                    border-radius:8px;
                    font-family:Arial,Helvetica,sans-serif;
                    box-shadow:0 3px 8px rgba(0,0,0,0.08);
                    max-width:520px;
                    margin:auto;
                    text-align:center;
                    line-height:1.6;
                '>

                <div style='font-size:22px;font-weight:600;color:#c0392b'>
                🚫 Post Removed
                </div>

                <div style='margin-top:10px;font-size:15px;color:#333'>
                This post violated our community safety guidelines and has been automatically removed.
                </div>

                {$keyword_block}

                </div>
                ";

                ossn_trigger_message($message,'warning');
            }

            // WARN CASE
            if($result['recommended_action'] === "WARN" && !$post_deleted){

                if($post_obj){
                    $post_obj->data->moderation_flag = 'offensive';
                    $post_obj->data->moderation_keywords = $keywords;
                    $post_obj->save();
                }

                $keyword_block = "";
                if(!empty($keywords)){
                    $keyword_block = "
                    <div style='margin-top:12px;font-size:14px'>
                    <b>Detected terms:</b><br>
                    <span style='color:#c0392b;font-weight:bold'>{$keywords}</span>
                    </div>";
                }

                $message = "
                <div style='
                    background:#fff9db;
                    border:1px solid #f2c94c;
                    border-left:6px solid #f39c12;
                    padding:22px;
                    border-radius:8px;
                    font-family:Arial,Helvetica,sans-serif;
                    box-shadow:0 3px 8px rgba(0,0,0,0.08);
                    max-width:520px;
                    margin:auto;
                    text-align:center;
                    line-height:1.6;
                '>

                <div style='font-size:22px;font-weight:600;color:#d68910'>
                ⚠ Content Warning
                </div>

                <div style='margin-top:10px;font-size:15px;color:#333'>
                Our automated moderation system detected potentially offensive language in this post.
                </div>

                {$keyword_block}

                <div style='margin-top:14px;font-size:13px;color:#777'>
                Please review your content and ensure it follows community guidelines.
                </div>

                </div>
                ";

                ossn_trigger_message($message,'warning');
            }
        }

    }catch(Exception $e){
        error_log("AI moderation error: ".$e->getMessage());
    }

    // Re-fetch post AFTER moderation metadata is saved
    $get = false;
    if(!$post_deleted){
        $get = $OssnWall->GetPost($guid);
    }

    // AJAX RESPONSE AFTER MODERATION
    if(ossn_is_xhr()){
        if($get){
            $get = ossn_wallpost_to_item($get);
            ossn_set_ajax_data(array(
                'post' => ossn_wall_view_template($get)
            ));
        }
        exit;
    }

    

    if(!$post_deleted){
        ossn_trigger_message(ossn_print('post:created'));
    }

    if(isset($OssnWall->OssnFile) && isset($OssnWall->OssnFile->error)){
        ossn_trigger_message(
            $OssnWall->OssnFile->getFileUploadError($OssnWall->OssnFile->error),
            'error'
        );
    }

    redirect(REF);

}else{

    if(isset($OssnWall->OssnFile) && isset($OssnWall->OssnFile->error)){
        ossn_trigger_message(
            $OssnWall->OssnFile->getFileUploadError($OssnWall->OssnFile->error),
            'error'
        );
    }else{
        ossn_trigger_message(ossn_print('post:create:error'),'error');
    }

    redirect(REF);
}
