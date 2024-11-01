<?php
/*
Plugin Name: Yahoo status
Plugin URI: http://as247.vui360.com/blog/yahoo-status/
Description: Display your Yahoo Status and your Yahoo Status Message on the widget!
Version: 1.1
Author: As247
Author URI: http://as247.vui360.com
Copyright 2009  As247  (email : as_3345@yahoo.com)
*/
if(!defined('ABSPATH')){
	require_once(dirname(dirname(dirname(dirname(__FILE__))))."/wp-load.php");
    require_once('yahoo.php');
    $my_yahoo_id=get_option('yahoo_status-my-yahoo-id');
    $tmp_yahoo_id=get_option('yahoo_status-tmp-yahoo-id');
    $tmp_yahoo_pass=get_option('yahoo_status-tmp-yahoo-pass');
	$friend=get_option('yahoo_status-friend-cache');
    $time_interval=60;
	$last_time=$friend['lastupdate'];
	if(!$friend||time()-$last_time>$time_interval){
		$botchanged=get_option('yahoo-status-bot-changed');
		$yahoo=new Yahoo($tmp_yahoo_id,$tmp_yahoo_pass,$botchanged);
		if($botchanged){
			update_option('yahoo-status-bot-changed',false);
		}
		$friend=$yahoo->get_full_friend_info($my_yahoo_id);
		$friend['lastupdate']=time();
		update_option('yahoo_status-friend-cache',$friend);
	}
    
    if(function_exists('s4w_convert_smilies')){
            $status_message=s4w_convert_smilies($friend['status_text']);
		}elseif(function_exists('convert_smilies')){
            $status_message=convert_smilies($friend['status_text']);
		}else
            $status_message=$friend['status_text'];
        $status=$friend['status'];
    @header("Cache-Control: no-cache");
        if(get_option('yahoo_status-show-status')&&$status){
            echo '<img src="'.get_option( 'siteurl' ).'/wp-content/plugins/yahoo-status/imgs/'.$status.'.gif"/> '.$status;
        }
        if($status_message)
		echo '<p>'.$status_message.'</p>';
		
		echo $after_widget;
    die;
}

function yahoo_status(){
	require_once('yahoo.php');
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;


	
	function yahoo_status_widget($args) {
       
		extract($args);
        $yahoo_status_url=get_bloginfo('wpurl')."/wp-content/plugins/yahoo-status/yahoo_status.php";
		$title =get_option('yahoo_status-title');
        if(!$title)$title="My yahoo status";
		echo $before_widget.$before_title.$title.$after_title;
		echo '<ul><li><div id="yahoo-status-div">Loading...</div></li></ul>';
		echo '<script>function loadyahoostatus(){jQuery("#yahoo-status-div").load("'.$yahoo_status_url.'");setTimeout("loadyahoostatus()",10000);} loadyahoostatus();</script>';
		echo $after_widget;
	}
    function yahoo_status_widget_control(){
        $title=get_option('yahoo_status-title');
        if(!$title)$title="My yahoo status";
        if($_POST['yahoo_status-submit']){
            update_option('yahoo_status-title',strip_tags($_POST['yahoo_status-title']));
            update_option('yahoo_status-show-status',(bool)$_POST['yahoo_status-show-status']);
        }
        echo '<p style="text-align: left;"><label for="yahoo_status-title">';
		_e('Title', 'yahoo_status');
		echo ': </label><input type="text" id="yahoo_status-title" name="yahoo_status-title" value="'.htmlspecialchars(stripslashes($title)).'" /></p>'."\n";
        echo '<p style="text-align: left;"><label for="yahoo_status-show-status">';
		_e('Show status', 'yahoo_status');
		echo ': </label><input type="checkbox" name="yahoo_status-show-status" id="yahoo_status-show-status" value="1" ';echo get_option('yahoo_status-show-status')?"checked":"";echo "/></p>"."\n";
        echo '<input type="hidden" id="yahoo_status" name="yahoo_status-submit" value="1" />'."\n";
    }
	register_sidebar_widget(__('Yahoo Status','yahoo_status'), 'yahoo_status_widget');
    register_widget_control(array('Yahoo Status', 'yahoo_status'), 'yahoo_status_widget_control');
}
function yahoo_status_option(){
    add_options_page('Yahoo Status', 'Yahoo Status', 5, basename(__FILE__), 'yahoo_status_option_page');
}
function yahoo_status_option_page(){


    if($_POST['options_save']){
        $my_id=strip_tags($_POST['my-yahoo-id']);
        $bot_id=strip_tags($_POST['temp-yahoo-id']);
        $bot_pass=strip_tags($_POST['temp-yahoo-password']);
		
		$tmp_yahoo_id=get_option('yahoo_status-tmp-yahoo-id');
		$tmp_yahoo_pass=get_option('yahoo_status-tmp-yahoo-pass');
        if($bot_id!=$tmp_yahoo_id||$tmp_yahoo_pass!=$bot_pass){
            //bot change?
            update_option('yahoo-status-bot-changed',true);
        }
        update_option('yahoo_status-my-yahoo-id',$my_id);
        update_option('yahoo_status-tmp-yahoo-id',$bot_id);
        update_option('yahoo_status-tmp-yahoo-pass',$bot_pass);
    }
    $my_yahoo_id=get_option('yahoo_status-my-yahoo-id');
    $tmp_yahoo_id=get_option('yahoo_status-tmp-yahoo-id');
    $tmp_yahoo_pass=get_option('yahoo_status-tmp-yahoo-pass');
	
	if($_POST['status_update_now']){
		$botchanged=get_option('yahoo-status-bot-changed');
		$yahoo=new Yahoo($tmp_yahoo_id,$tmp_yahoo_pass,$botchanged);
		if($botchanged){
			update_option('yahoo-status-bot-changed',false);
		}
		$friend=$yahoo->get_full_friend_info($my_yahoo_id);
		$friend['lastupdate']=time();
		update_option('yahoo_status-friend-cache',$friend);
	}
	$friend=get_option('yahoo_status-friend-cache');
	$status=$friend['status'];
	if(function_exists('s4w_convert_smilies')){
            $status_message=s4w_convert_smilies($friend['status_text']);
		}elseif(function_exists('convert_smilies')){
            $status_message=convert_smilies($friend['status_text']);
		}else
            $status_message=$friend['status_text'];
?>
<div class="wrap">
<h2>Yahoo status</h2>
<table class="fixed">
	<thead>
		<th width=50%>Setting</th>
		<th width=50%>Current Status</th>
<tr><td>
<form action="options-general.php?page=yahoo_status.php" method="post">
    <fieldset>
    	
    
        <table class="form-table">
        	<tr>
        		<th>Your yahoo id</th>
                <td><input name="my-yahoo-id" type="text" value="<?php echo $my_yahoo_id?>"/>
                <br /><span class="description">Yahoo id you want to get status from</span>

                </td>
        	</tr>
            <tr>
            	<th valign="top">Temporary yahoo id</th>
            	<td>
				<input name="temp-yahoo-id" type="text" value="<?php echo $tmp_yahoo_id;?>"/>
                <br /><span class="description">This yahoo id is used to get your yahoo status</span>
				
                </td>
        	</tr>
            <tr>
            	<th valign="top">Temporary yahoo password</th>
            	<td><input name="temp-yahoo-password" type="text" value="<?php echo $tmp_yahoo_pass;?>"/>
                </td>
        	</tr>
		</table>        
        
        <p class="submit">
        	<input type="submit" name="options_save" class="button-primary" value="Save changes" />
        </p>
    </fieldset>
</form>
</td>
<td>

<form action="options-general.php?page=yahoo_status.php" method="post">
    <fieldset>
    	
    
        <table class="form-table">
        	<tr>
        		<th>Yahoo id</th>
                <td><?php echo $my_yahoo_id?></td>
        	</tr>
            <tr>
            	<th valign="top">Status</th>
            	<td>
					<?php echo '<img src="'.get_option( 'siteurl' ).'/wp-content/plugins/yahoo-status/imgs/'.$status.'.gif"/> '.$status;?>
                </td>
        	</tr>
            <tr>
            	<th valign="top">Status messages</th>
            	<td><?php echo $status_message;?>
                </td>
        	</tr>
		</table>        
        
        <p class="submit">
        	<input type="submit" name="status_update_now" class="button-primary" value="Update now" />
        </p>
    </fieldset>
</form>

</td>
</table>
</div>
<?php
}
add_action('admin_menu','yahoo_status_option');
add_action('plugins_loaded','yahoo_status');
wp_enqueue_script("jquery"); 
?>