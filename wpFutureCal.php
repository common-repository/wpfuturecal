<?php
/*
Plugin Name: wpFutureCal
Plugin URI: http://plus1daily.com/plugins/wpfuturecal/
Description: Instantly see future scheduled posts in a calendar
Version: 2.1
Author: Tim Linden
Author URI: http://www.timlinden.com
*/




if(!function_exists ( 'plus1plugins_admin' ) ) {
	/*
	 *  To keep the menu clean, all our plugins have this function to create one menu.
	 */
	function plus1plugins() { $icon_url = plugins_url($path = '/wpfuturecal').'/icon.png'; add_menu_page( 'Plus1Plugins', 'Plus1Plugins', 'manage_options', 'plus1plugins', 'plus1plugins_admin', $icon_url); } if (!get_option("plus1plugins_savecode")) { $plus1plugins_savecode = substr(md5(mt_rand(1, 999999)), 3, 10); update_option("plus1plugins_savecode", $plus1plugins_savecode); }
	function plus1plugins_admin() { $url = admin_url('admin.php?page=plus1plugins&auth='.get_option("plus1plugins_savecode")); if ($_GET["auth"] == get_option("plus1plugins_savecode")) { update_option("plus1plugins_authcode", get_option("plus1plugins_savecode")); } ?> <style>#blogfrm{margin: 150px auto auto; background-color: #0077B3; color: white; text-rendering: optimizelegibility;font-family: 'Open Sans', sans-serif; padding:10px;display:table;height: 200px;width: 600px;}#bloghead{color: #fff; font-size: 20px; font-family: 'Open Sans', sans-serif; font-weight: bold; text-align: center; line-height: 1.1em; margin-top:5px;}#blogtxt{background-color: #0077b3; border: 0 none; color: #fff; font-family: "Open Sans",sans-serif; font-size: 16px; font-weight: 300; line-height: 20px; padding-bottom: 10px; margin: auto; text-align: center; text-rendering: optimizelegibility;}#blognpt{background-color: #75c5ea; border: 2px solid #0185c1; color: #fff; font-size: 16px; font-weight: bold; padding: 10px; text-align: center; width: 100%; -webkit-box-sizing: border-box; /* Safari/Chrome, other WebKit */ -moz-box-sizing: border-box; /* Firefox, other Gecko */ box-sizing: border-box; /* Opera/IE 8+ */}#blogbtn{background-color: #F15B29; border: 2px solid #0185c1; color: #fff; font-family: "Open Sans",sans-serif; font-size: 16px; font-weight: bold; padding: 10px; text-align: center; width: 100%; border-radius: 0px;}</style><div id="blogfrm"><form method=POST action="https://www.rocketresponder.com/subscribe/"><input type="hidden" name="ID" value="plus1plugins"><input type="hidden" name="return" value="<?php echo $url; ?>"><input type="hidden" name="confirm" value="<?php echo $url; ?>"><h3 id="bloghead">Register Your Copy of wpFutureCal</h3><p id="blogtxt"><?php if (get_option("plus1plugins_authcode") == get_option("plus1plugins_savecode")) { echo "Thank you for registering!"; } else { echo "It's free, takes a few seconds, and you only have to do it once ;-)";?></p><p><input name=email type=email placeholder="Enter your email here.." id="blognpt" value="<?php echo bloginfo('admin_email'); ?>"><input type=submit value="Register Now" id="blogbtn"><?php } ?></p></form></div><?php }
	add_action( 'admin_menu', 'plus1plugins' );
}


add_action( 'wp_ajax_wpfuturecal', 'wpFutureCal_callback' );

function wpFutureCal_callback() {
	global $wpdb;
	
	$year = intval( $_POST['year'] );
	$month = intval( $_POST['month'] );

	wpFutureCal_show($year, $month);
	
	wp_die();
}

function wpFutureCal_meta() {
	?>
	<style>
	.wpfuturecalgood { background-color: #7BC67F; }
	.wpfuturecalgood A { color: black; text-decoration: none; }
	.wpfuturecaldone { background-color: #F7DE00; }
	.wpfuturecaldone A { color: black; text-decoration: none; }
	.wpfuturecalbad  { background-color: #C93434; }
	.wpfuturecalcalendar { width: 100%; }
	.wpfuturecalcalendar td {  color: white;
	    font-size: 15px;
	    font-weight: bold;
	    height: 12px;
	    text-align: center;
	    width: 14%;
	    border: 1px solid black; }
	.wpfuturecalcalendar-month { background-color: #00345b; color: white; }
	.wpfuturecalcalendar th { color: black; background-color: #C4CAD0; text-align: center; border: 1px solid; font-size: 8px;  }
	</style>
	
	<div id="wpfuturecal">
	<?php
	
	wpFutureCal_show();
	
	?>
	</div>
	
	<script type="text/javascript" >
	function wpfuturecal(year, month) {
		var data = {
			'action': 'wpfuturecal',
			'year': year,
			'month': month
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#wpfuturecal').html(response);
		});
	}
	</script>
	<?php
}

function wpFutureCal_show($year = null, $month = null) {
		global $wpdb;
		
		if (is_null($year)) { $year = date("Y"); }
		if (is_nulL($month)) { $month = date("m"); }
		
		if (get_option("plus1plugins_authcode") != get_option("plus1plugins_savecode") || get_option("plus1plugins_savecode") == "") {
			$url = admin_url('admin.php?page=plus1plugins');
			$image = plugins_url($path = '/wpfuturecal').'/activate.png';
			echo "<center><a href='{$url}'><img src='{$image}' border='0'></a></center>";
		} else { 
			$query = "SELECT DATE( post_date ) as date, ID, post_title, post_status FROM {$wpdb->posts} WHERE YEAR( post_date ) = '$year' AND MONTH( post_date ) = '$month' AND post_type = 'post'";
			$results = $wpdb->get_results($query);
			
			foreach ($results as $result) {
				
				if ($result->post_status == "future") {
					$days[$result->date] = array(1, $result->ID, $result->post_title);
				} else {
					$days[$result->date] = array(2, $result->ID, $result->post_title);
				}
			}
	
			$wpFutureCal = wpFutureCal_generate_calendar($year, $month, $days);

			$time = mktime(1, 1, 1, $month, 1, $year);
			if ($time > time()) {
				if ($month == 1) {
					$y = $year - 1;
					$m = 12;
				} else {
					$m = $month - 1;
					$y = $year;
				}

				$prev = "<a href='javascript:wpfuturecal($y, $m);'>Previous</a>";
			} else {
				$prev = "";
			}
			
			
	
			if ($month == 12) {
				$m = 1;
				$y = $year + 1;
			} else {
				$m = $month + 1;
				$y = $year;
			}
			$next = "<a href='javascript:wpfuturecal($y, $m);'>Next</a>";
			
			echo $wpFutureCal;
			echo "<table style='width:100%;'><tr><td>{$prev}</td><td align=right>{$next}</td></tr></table>";
		}
}



function wpFutureCal_generate_calendar($year, $month, $days = array(), $show = 0){
	$today = date("Y-m-d");

	$day_name_length = 3;
	$month_href = NULL;
	$first_day = 0;
	$pn = array();

	$first_of_month = gmmktime(0,0,0,$month,1,$year);
	#remember that mktime will automatically correct if invalid dates are entered
	# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
	# this provides a built in "rounding" feature to generate_calendar()

	$day_names = array(); #generate all the day names according to the current locale
	for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
		$day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

	list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
	$weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day
	$title   = htmlentities(ucfirst($month_name)).'&nbsp;'.$year;  #note that some locales don't capitalize month and day names

	#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
	@list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
	if($p) $p = '<span class="wpfuturecalcalendar-prev">'.($pl ? '<a href="'.htmlspecialchars($pl).'">'.$p.'</a>' : $p).'</span>&nbsp;';
	if($n) $n = '&nbsp;<span class="wpfuturecalcalendar-next">'.($nl ? '<a href="'.htmlspecialchars($nl).'">'.$n.'</a>' : $n).'</span>';
	$calendar = '<table class="wpfuturecalcalendar" cellspacing=1>'."\n".
		'<caption class="wpfuturecalcalendar-month">'.$p.($month_href ? '<a href="'.htmlspecialchars($month_href).'">'.$title.'</a>' : $title).$n."</caption>\n<tr>";

	if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
		#if day_name_length is >3, the full name of the day will be printed
		foreach($day_names as $d)
			$calendar .= '<th abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
		$calendar .= "</tr>\n<tr>";
	}

	if($weekday > 0) $calendar .= '<td colspan="'.$weekday.'">&nbsp;</td>'; #initial 'empty' days
	for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
		if($weekday == 7){
			$weekday   = 0; #start a new week
			$calendar .= "</tr>\n<tr>";
		}
		$date = $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);
		if ($show == 0) {
			if ($days[$date][0] == 1) {
				$calendar .= "<td class='wpfuturecalgood'><a href='".get_edit_post_link($days[$date][1])."' title='".htmlspecialchars($days[$date][2], ENT_QUOTES)."'>$day</a></td>";
			} elseif ($days[$date][0] == 2) {
				$calendar .= "<td class='wpfuturecaldone'><a href='".get_edit_post_link($days[$date][1])."' title='".htmlspecialchars($days[$date][2], ENT_QUOTES)."'>$day</a></td>";
			} else {
				$calendar .= "<td class='wpfuturecalbad'>$day</td>";
			}
		} else {
			$calendar .= "<td>".number_format($days[$date], 0)."</td>";
		}
	}
	if($weekday != 7) $calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; #remaining "empty" days

	return $calendar."</tr>\n</table>\n";
}

function wpFutureCal_setup() {
	add_meta_box('wpFutureCal_meta', 'wpFutureCal', "wpFutureCal_meta", "post", "side", "high");
}

function wpFutureCal_dashboard() {
	wp_add_dashboard_widget('wpFutureCal_dashboard_widget','wpFutureCal','wpFutureCal_meta');
}

add_action('admin_menu', 'wpFutureCal_setup');
add_action( 'wp_dashboard_setup', 'wpFutureCal_dashboard' );

?>