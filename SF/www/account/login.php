<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

Header( "Expires: Wed, 11 Nov 1998 11:11:11 GMT"); 
Header( "Cache-Control: no-cache"); 
Header( "Cache-Control: must-revalidate"); 

require('../include/pre.php');

$LANG->loadLanguageMsg('account/account');

if (!session_issecure() && ($GLOBALS['sys_https_host'] != "")) {
    //force use of SSL for login
    header('Location: https://'.$GLOBALS['sys_https_host'].'/account/login.php');
}

// ###### first check for valid login, if so, redirect

if ($login) {
    list($success, $status) = session_login_valid($form_loginname,$form_pw);
    if ($success) {
	/*
	  You can now optionally stay in SSL mode
	*/
	$use_ssl = session_issecure()
		&& $GLOBALS['sys_https_host'] != ""
		&& ($GLOBALS['sys_force_ssl']
		    || !$GLOBALS['sys_stay_in_ssl']
		    || $HTTP_POST_VARS['stay_in_ssl']
		    );

	if ($return_to) {
	    // if return_to URL start with a protocol name then take as is
	    // otherwise prepend the proper http protocol
	    if (preg_match("/^\s*\w*:\/\//", $return_to)) {
		header("Location: $return_to");
	    } else {
		if ($use_ssl) {
		    header("Location: https://".$GLOBALS['sys_https_host'].$return_to);
		} else {
		    header("Location: http://".$GLOBALS['sys_default_domain'].$return_to);
		}
	    }
	    exit;
	} else {
	    if ($use_ssl) {
		header("Location: https://".$GLOBALS['sys_https_host']."/my/");
	    } else {
		header("Location: http://".$GLOBALS['sys_default_domain']."/my/");
	    }
	    exit;
	}
    }
}
if ($session_hash) {
	//nuke their old session
	session_cookie('session_hash','');
	db_query("DELETE FROM session WHERE session_hash='$session_hash'");
}
$HTML->header(array('title'=>$LANG->getText('account_login', 'title')));

if ($login && !$success) {

    if ($status == 'P') {
	echo "<P><B>".$LANG->getText('account_login', 'pending_title')."</B>";
	echo "<P>".$LANG->getText('account_login', 'pending_msg');
	echo "<P><A href=\"pending-resend.php?form_user=$form_loginname; \">[".$LANG->getText('account_login', 'resend_btn')."]</A><br><hr><p>";
    } else if ($status == 'S') {
	echo "<P><B>".$LANG->getText('account_suspended', 'title')."</B>";
	echo "<P>".$LANG->getText('account_suspended', 'message', array($GLOBALS['sys_email_contact']));
	echo "<br><hr><p>";
    } else {
	echo '<h2><span class="feedback">'. $feedback .'</span></H2>';
    } //end else
}

if ($GLOBALS['sys_https_host']) {
    $form_url = "https://".$GLOBALS['sys_https_host'];
} else {
    $form_url = "http://".$GLOBALS['sys_default_domain'];
}
?>

<p>
<h2><?php print $LANG->getText('account_login', 'title', array($GLOBALS['sys_Name'])); ?>
<?php print ($GLOBALS['sys_https_host'] != "" ? ' (Secure)':''); ?>
</h2>
<p>
<span class="highlight"><B><?php print $LANG->getText('account_login', 'cookies'); ?>.</B></span>
<P>
<form action="<?php echo $form_url; ?>/account/login.php" method="post" name="form_login">
<INPUT TYPE="HIDDEN" NAME="return_to" VALUE="<?php echo $return_to; ?>">
<p>
<?php print $LANG->getText('account_login', 'name'); ?>:
<br><input type="text" name="form_loginname" VALUE="<?php echo $form_loginname; ?>">
<p>
<?php print $LANG->getText('account_login', 'password'); ?>:
<br><input type="password" name="form_pw">
<P>
<?php
// Only show the stay in SSL mode if the server is SSL enabled
// and it is not forced to operate in SSL mode
// and the stay in SSL check box can be shown
if ( $GLOBALS['sys_https_host'] != '' && $GLOBALS['sys_force_ssl'] == 0 &&
     $GLOBALS['sys_stay_in_ssl'] == 1 ) {
    echo '<INPUT TYPE="CHECKBOX" NAME="stay_in_ssl" VALUE="1" '.
    (((browser_is_ie() && browser_get_version() < '5.1') || !session_issecure()) ?'':'CHECKED').'>'.
    $LANG->getText('account_login', 'name').'
<p>
';

    if  (browser_is_ie() && browser_get_version() < '5.1') {
	echo $LANG->getText('account_login', 'msie_pb');
    }
}
?>
<p>
<input type="submit" name="login" value="<?php echo $LANG->getText('account_login', 'login_btn'); ?>">
</form>
<P>
<?php echo $LANG->getText('account_login', 'lost_pw',array($GLOBALS['sys_email_admin'],$GLOBALS['sys_name'])); ?>
<P>
<?php echo $LANG->getText('account_login', 'create_acct',array($GLOBALS['sys_name'],$GLOBALS['sys_org_name'])); ?>

<SCRIPT language="JavaScript"> <!-- 
    document.form_login.form_loginname.focus();
// --></SCRIPT>

<?php

$HTML->footer(array());

?>
