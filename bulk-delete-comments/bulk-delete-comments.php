<?php
 /*
	Plugin Name: Bulk Delete Comments
	Description: Effortlessly bulk delete comments and clean up your WordPress site with ease. Remove all comments, including spam, unapproved, and trash, or filter by post and category in a single click.
	Author: Shah Alom
	Version: 2.3
*/
  
  add_action('admin_menu', 'dac_menu');

  function dac_menu() {
		
		add_options_page(
			'Bulk Delete Comments',  
			'Bulk Delete Comments',  
			'manage_options',        
			'delete_all_comments',   
			'dac_interace_page'      
		);
	}
  
  
  add_filter('comments_open', 'dac_disable_comments', 20, 2);
  add_filter('pings_open', 'dac_disable_comments', 20, 2);
  add_filter('comments_array', 'dac_hide_comments', 10, 2);
  
  add_action('init','dac_handler_init',10,2);

  add_filter( 'plugin_action_links_'.plugin_basename( plugin_dir_path( __FILE__ ) . 'bulk-delete-comments.php'), 'dac_admin_plugin_settings_link' );
  
  

  register_activation_hook(__FILE__, 'dac_activate_redirect');

  function dac_activate_redirect() {

	set_transient('dac_plugin_activated_redirect', true, 30); 
 }


  add_action('admin_init', 'dac_plugin_redirect_after_activation');

  function dac_plugin_redirect_after_activation() {

		if (get_transient('dac_plugin_activated_redirect')) {
		  
			delete_transient('dac_plugin_activated_redirect');
			
		   
			wp_redirect(admin_url('admin.php?page=delete_all_comments'));
	  
		}
	}

  
  function dac_handler_init()
  {
	global $wpdb;
	  	
	

	if(current_user_can('edit_posts'))
	{	
	 if(isset($_POST['dac_comments']) &&  wp_verify_nonce( $_POST['dac_comments'], 'dac')){
		
		$commentsData=get_option('_transient_wc_count_comments');
		if (empty($commentsData)) {
        $commentsData = new stdClass(); // Set to an empty object
    }	
		
		if(isset($_POST['dallc']) && !empty($_POST['dallc']))
		{	
	
			$commentsData->total_comments=0;
			$commentsData->all=0;
			$commentsData->moderated=0;
			$commentsData->spam=0;
			$commentsData->approved=0;
			$commentsData->trash=0;
			$commentsData->approved=0;
			
				
		
		}
		else if(isset($_POST['dsc']) && !empty($_POST['dsc']))
		{
			$commentsData->spam=0;
			
		}
		else if(isset($_POST['dac']) && !empty($_POST['dac']))
		{
			$commentsData->moderated=0;
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_approved=%s",'0') 
					 );
			
			$total_comments=count($results);
				
			$commentsData->all=$commentsData->all-$total_comments;
			$commentsData->total_comments=$commentsData->total_comments-$total_comments;
			
			
		}
		else if(isset($_POST['dtc']) && !empty($_POST['dtc']))
		{
			$commentsData->trash=0;
		}
		else if(isset($_POST['ducp']) && !empty($_POST['ducp']))
		{
			$postId=sanitize_text_field(intval($_POST['postid']));
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_approved=%s and comment_post_ID=%d",'0',$postId) 
					 ) ;
					 
			$total_comments=count($results);	
			
			$commentsData->moderated=$commentsData->moderated-$total_comments;	
			$commentsData->all=$commentsData->all-$total_comments;	
			$commentsData->total_comments=$commentsData->total_comments-$total_comments;	
			
			
		
		}			 

		update_option('_transient_wc_count_comments',$commentsData);
	  }
	}
  }
  function dac_disable_comments()
  {
	 $dac_disable_option=get_option('dac_disable_option');

	if($dac_disable_option=="1")
		return false;
	
	return true;
	  
  }

  function dac_hide_comments($comments)
  {	
	$dac_hide_option=get_option('dac_hide_option');
	
	if($dac_hide_option=="1")
			return array();
	
	return $comments;
  }

  function dac_admin_plugin_settings_link( $links ) { 
	$settings_link = '<a href="admin.php?page=delete_all_comments">Settings</a>';
    array_unshift($links, $settings_link); // Adds the link to the beginning of the array
    return $links;
	}

  function dac_interace_page()
  {
	wp_enqueue_script(
        'dac-form-handler', 
        plugins_url( 'js/script.js', __FILE__ ), 
        array('jquery'), 
        '1.0.0',
        true 
    );
	$error=0;
	if(current_user_can('edit_posts'))
	{
		global $wpdb;
		$notificationMessage="";
		
		
	if(isset($_POST['dac_comments']) && !empty($_POST['dac_comments']))
   {
	

	 if (isset( $_POST['dac_comments']) &&  wp_verify_nonce( $_POST['dac_comments'], 'dac')) 
	{
		if(isset($_POST['dall_disable_submit']) && !empty($_POST['dall_disable_submit']))
		{
			$dac_disable=0;
			$dac_hide=0;
			if(!empty($_POST['dac_disable']))
			{
				$dac_disable=1;
				
			}
			if(!empty($_POST['dac_hide']))
			{
				$dac_hide=1;
			}

			
			update_option('dac_disable_option',$dac_disable);
			update_option('dac_hide_option',$dac_hide);
			
			$notificationMessage="Options saved successfully";
			
		}
		else if(isset($_POST['dallc']) && !empty($_POST['dallc']))
		{
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where 1=%d",1) 
					 );
			
		 if(!empty($results))
		{
			
			
			
			
			$query=$wpdb->prepare("delete from {$wpdb->prefix}comments where 1=%d",1);
			$response=$wpdb->query($query);
			if($response)
			{
				$query=$wpdb->prepare("update {$wpdb->prefix}posts set comment_count=%d",0);
				$response=$wpdb->query($query);
				$notificationMessage="All The Comments Deleted Successfully";
			}
			else
			{
				$error=1;
				$notificationMessage="Sorry! Something Went Wrong";
			}
		}
		else
		{
			$error=1;
			$notificationMessage="There are no Comments to be deleted";
		}
		
		}
		else if(isset($_POST['dsc']) && !empty($_POST['dsc']))
		{
			
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_approved=%s",'spam') 
					 );
				 
					
		if(!empty($results))
		{
			$query=$wpdb->prepare("delete from {$wpdb->prefix}comments where comment_approved=%s",'spam');
			$response=$wpdb->query($query);
			if($response)
			{
				$notificationMessage="Spam Comments Delete Successfully";	
			}
			else
			{
				$error=1;
				$notificationMessage="Sorry! Something Went Wrong";
			}
		}
		else
		{
			$error=1;
			$notificationMessage="There is no spam Comments to be deleted";
		}
		}
		else if(isset($_POST['dac']) && !empty($_POST['dac']))
		{
		
			
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_approved=%s",'0') 
					 );
			
	
				
					
		if(!empty($results))
		{
			$query=$wpdb->prepare("delete from {$wpdb->prefix}comments where comment_approved=%s",'0');
			$response=$wpdb->query($query);
			if($response)
			{
				
				$notificationMessage="Unapproved Comments Delete Successfully";	
			}
			else
			{
				$error=1;
				$notificationMessage="Sorry! Something Went Wrong";
			}
		}
		else
		{
				$error=1;
				$notificationMessage="There is no unapproved Comments to be deleted";
		}
			
		}
		else if(isset($_POST['dapvc']) && !empty($_POST['dapvc']))
		{
		
			
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_approved=%s",'1') 
					 );
			
	
				
					
		if(!empty($results))
		{
			$query=$wpdb->prepare("delete from {$wpdb->prefix}comments where comment_approved=%s",'1');
			$response=$wpdb->query($query);
			if($response)
			{
				
				$notificationMessage="Approved Comments Delete Successfully";	
			}
			else
			{
				$error=1;
				$notificationMessage="Sorry! Something Went Wrong";
			}
		}
		else
		{
				$error=1;
				$notificationMessage="There is no approved Comments to be deleted";
		}
			
		}
		
		else if(isset($_POST['dtc']) && !empty($_POST['dtc']))
		{
			
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_approved=%s",'trash') 
					 );
				 
					
		if(!empty($results))
		{
			$query=$wpdb->prepare("delete from {$wpdb->prefix}comments where comment_approved=%s",'trash');
			$response=$wpdb->query($query);
			if($response)
			{
				$notificationMessage="Trash Comments Delete Successfully";	
			}
			else
			{
				$error=1;
				$notificationMessage="Sorry! Something Went Wrong";
			}
		}
		else
		{
			$error=1;
			$notificationMessage="There is no trash Comments to be deleted";
		}
			
		}
		else if(isset($_POST['ducp']) && !empty($_POST['ducp']))
		{
			$postId=sanitize_text_field(intval($_POST['postid']));
			$results = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_approved=%s and comment_post_ID=%d",'0',$postId) 
					 ) ;
				 
					
			if(!empty($results))
			{
				$query=$wpdb->prepare("delete from {$wpdb->prefix}comments where comment_approved=%s and comment_post_ID=%d",'0',$postId);
				
				$response=$wpdb->query($query);
				if($response)
				{
					$notificationMessage="Unapproved Comments for selected post deleted Successfully";	
				}
				else
				{
					$error=1;
					$notificationMessage="Sorry! Something Went Wrong";
				}
			}
			else
			{
					$error=1;
					$notificationMessage="There is no unapproved Comments for selected post";
			}
		
		}
		else if(isset($_POST['dapc']) && !empty($_POST['dapc']))
		{
			$postId=sanitize_text_field(intval($_POST['postid1']));
			if($postId)
			{
				$results = $wpdb->get_results( 
							$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_post_ID=%d",$postId) 
						 );
				
				 if(!empty($results))
				{
					$query=$wpdb->prepare("delete from {$wpdb->prefix}comments where 1=%d",1);
					$response=$wpdb->query($query);
					if($response)
					{
						$query=$wpdb->prepare("update {$wpdb->prefix}posts set comment_count=%d where ID=%d",0,$postId);
						$response=$wpdb->query($query);
						$notificationMessage="All The Comments Deleted For selected post Successfully";
					}
					else
					{
						$error=1;
						$notificationMessage="Sorry! Something Went Wrong";
					}
				}
				else
				{
					$error=1;
					$notificationMessage="There are no Comments for selected post";
				}
			}
			else
			{
				die("Invalid Data!!! Unable To Process");
			}
		}
		else if(isset($_POST['ducc']) && !empty($_POST['ducc']))
		{
			$catId=sanitize_text_field(intval($_POST['catid']));
			if($catId)
			{
				$results = $wpdb->get_results( 
							$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}term_relationships where term_taxonomy_id=%d",$catId) 
						 );
				$postIDs=array();
				foreach($results as $key=>$val)
				{
					$postIDs[]=$val->object_id;
				}
				$how_many=count($postIDs);
				$placeholders = array_fill(0, $how_many, '%d');
				$format = implode(', ', $placeholders);
				$comments=$wpdb->get_results( 
							$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments where comment_post_ID IN($format) and comment_approved='0'",$postIDs));                 ;
				
				if(!empty($comments))
				{
					 $query=$wpdb->prepare("delete FROM {$wpdb->prefix}comments where comment_post_ID IN($format) and comment_approved='0'",$postIDs);
					 $response=$wpdb->query($query);
					if($response)
					{
						$notificationMessage="Unapproved Comments for selected Category deleted Successfully";	
					}	
				}
				else
				{
					$error=1;
					$notificationMessage="There are no Unapproved comment for selected category";
				}
		}
		else
		{
				die("Invalid Data!!! Unable To Process");
		}
	}
	
	
		
	}
	else
	{
		die("Sorry! Your Noonce didn't verify");
	}
	}
	
	
	$dac_disable_option=get_option('dac_disable_option');
	$dac_hide_option=get_option('dac_hide_option');
	$statData = $wpdb->get_results( 
						$wpdb->prepare("select count(*) as total_comments, SUM(comment_approved=%s) as spamcount,  SUM(comment_approved='0') as unpcount,SUM(comment_approved='1') as apvcount,SUM(comment_approved='trash') as trashcount from wp_comments","spam") 
					 );

	
					 
	$postData = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}posts where post_status=%s",'publish') 
					 );
		$postSelect='<select name="postid">';
		$postSelect1='<select name="postid1">';
		foreach($postData as $key=>$val)
		{
			$postSelect.='<option value="'.$val->ID.'">'.$val->post_title.'</option>';
			$postSelect1.='<option value="'.$val->ID.'">'.$val->post_title.'</option>';
		}
		$postSelect.='</select>';
		$postSelect1.='</select>';	
		$dacCategories=get_categories();
		$catSelect='<select name="catid">';
		foreach($dacCategories as $key=>$val)
		{
			$catSelect.='<option value="'.$val->term_id.'">'.$val->name.'</option>';
		}
		$catSelect.='</select>';
		
		$html='<div  id="poststuff">
    <div>
        <h2 style="font-size:25px">Settings → Bulk Delete Comments</h2><br />';
	if(isset($_POST) && !empty($_POST))
	{
		
		
		
		$class="updated";
		if($error==1)
			$class="notice notice-error";
		
		if(!empty($notificationMessage))
			$html.='<div id="message" class="'.$class.' fade" style="margin:0px 20px 10px 2px;"><p><strong>'.$notificationMessage.'</strong></p></div>';
	}
	$html.='<div class="postbox">
			<h3 class="hndle">
			 <span>Disable Comments</span>
			</h3>
			<div class="inside">
    <form action="" method="post">
		<p><input type="checkbox" name="dac_disable" ';
		
		if($dac_disable_option=="1")
				$html.= "checked";
		
		$html.= '/><label><b>EveryWhere</b>:Disable Comments on your entire website pages</label></p>
		<p style="color:#555">Everywhere: Select this option to disable the comment box on all pages/posts of your website, preventing users from adding new comments. Existing comments will remain visible, but the comment form will be hidden, ensuring that no new comments can be submitted while still displaying past interactions.</p>
		<p><input type="checkbox" name="dac_hide" ';
		
		if($dac_hide_option=="1")
				$html.= "checked";
		
		$html.= '/><label><b>EveryWhere</b>:Hide Comment on your entire website pages</label></p>
		<p style="color:#555">Selecting this will Hide all Existing comments on all pages/posts of your  website.</p>
		<input type="submit" name="dall_disable_submit" value="Submit" class="button button-primary" />'.wp_nonce_field( 'dac', 'dac_comments').'
	</form>
	</div>
	</div>
	
	<div class="postbox">
			<h3 class="hndle">
			 <span>Delete Comments By Following Options</span>
			</h3>
			<div class="inside">
    <form action="" method="post" onsubmit="return confirmAction();">
	<table>
		<tr>
		<tbody>
			<td width="300"><label>Delete All Comments</label></td><td width="300">Number of Comments: ';
			
	if($statData[0]->total_comments=="")
		$html.='0';
	else
		$html.=$statData[0]->total_comments;
		
	$html.='</td><td><input type="submit" name="dallc" value="Delete" class="button button-primary" style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold"  onclick="setConfirmMessage(\'Are you sure you want to delete all comments? This action can\\\'t be undone\');"/></td></tr>
		<tr><td><label>Delete Spam Comments</label></td><td>Number of Spam Comments: ';
	
	if($statData[0]->spamcount=="")
		$html.='0';
	else
		$html.=$statData[0]->spamcount;
	
	$html.='</td><td><input type="submit" name="dsc" value="Delete" class="button button-primary" style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold"  onclick="setConfirmMessage(\'Are you sure you want to delete all spam comments? This action can\\\'t be undone\');"/></td></tr>
		<tr><td><label>Delete Unapproved Comments</label></td><td>Number of Unapproved Comments: ';
	
	if($statData[0]->unpcount=="")
		$html.='0';
	else
		$html.=$statData[0]->unpcount;
		
	$html.='</td><td><input type="submit" name="dac" value="Delete" class="button button-primary" style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold"  onclick="setConfirmMessage(\'Are you sure you want to delete all unapproved comments? This action can\\\'t be undone\');"/></td></tr>
	<tr><td><label>Delete Approved Comments</label></td><td>Number of Approved Comments: ';
	
	if($statData[0]->apvcount=="")
		$html.='0';
	else
		$html.=$statData[0]->apvcount;
		
	$html.='</td><td><input type="submit" name="dapvc" value="Delete" class="button button-primary" style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold"  onclick="setConfirmMessage(\'Are you sure you want to delete all  approved comments? This action can\\\'t be undone\');"/></td></tr>
		<tr><td><label>Delete Trash Comments</label></td><td>Number of Trash Comments: ';
		
	if($statData[0]->trashcount=="")
		$html.='0';
	else
		$html.=$statData[0]->trashcount;
	
		
	$html.='</td><td><input type="submit" name="dtc" value="Delete" class="button button-primary"  style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold"  onclick="setConfirmMessage(\'Are you sure you want to delete all trash comments? This action can\\\'t be undone\');"/></td></tr>
		
		</tbody></table>'.wp_nonce_field( 'dac', 'dac_comments').'
			</form></div></div></div><p class="update-nag" style="margin:0px 20px 10px 2px;">Warning: Once Comments Deleted  Can\'t be restored.</p>';
			
		$html.='<div class="postbox">
			<h3 class="hndle">
			 <span>Delete Comments By Post</span>
			</h3>
			<div class="inside">
    <form action="" method="post" onsubmit="return confirmAction();">
	<table>
		
		<tr><td width="300"><label>Delete UnApproved Comments By Post/Category</label></td><td width="300">'.$postSelect.'</td><td><input type="submit" name="ducp" value="Delete" class="button button-primary" style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold" onclick="setConfirmMessage(\'Are you sure you want to delete all unapproved comments for selected post ? This action can\\\'t be undone\');"/></td></tr>	
		<tr><td><label>Delete All Comments By Post</label></td><td>'.$postSelect1.'</td><td><input type="submit" name="dapc" value="Delete" class="button button-primary"  style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold" onclick="setConfirmMessage(\'Are you sure you want to delete all comments for selected post? This action can\\\'t be undone\');"/></td></tr>
		<tr><td><label>Delete UnApproved Comments By Category</label></td><td>'.$catSelect.'</td><td><input type="submit" name="ducc" value="Delete" class="button button-primary"  style="background-color: #d63638; color: white;border-color:#d63638;font-weight:bold" onclick="setConfirmMessage(\'Are you sure you want to delete all comments for selected category? This action can\\\'t be undone\');"/></td></tr>	
	</table>'.wp_nonce_field( 'dac', 'dac_comments').'
			</form></div></div></div>';
		
		_e($html);
	}
	else
	{
		die("You don't have permission to access this page");
	}
  }

  function dac_enqueue_promotion_script() {
    // Get the URL of the plugin directory
    $script_url = plugin_dir_url(__FILE__) . 'js/boost-premium.js';

    // Register and enqueue the script
    wp_enqueue_script(
        'promotion_script',
        $script_url,
        array('jquery'),
        '1.1',
        true
    );
}

// Hook into admin and login pages to load the script
add_action('admin_enqueue_scripts', 'dac_enqueue_promotion_script');
add_action('login_enqueue_scripts', 'dac_enqueue_promotion_script');
?>