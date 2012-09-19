<fieldset>
<legend>Search</legend>
<p>Search is <em>inclusive</em> meaning results are drawn from machines 
that match any of the specified criteria.</p>
<form method="post" name="<?php echo $formname;?>" id="<?php echo $formname;?>">
<table>
	<tr><td>Hostname:</td><td><input type="text" name="hostname"/></td></tr>
  <tr><td>IP:</td><td><input type="text" name="ip"/></td></tr>
  <tr><td>Service version (e.x. "OpenSSH"):</td><td><input type="text" name="version"/></td></tr>
	<tr><td>&nbsp;</td><td><input type="submit" value="Search"/></td></tr>
</table>
<input type="hidden" name="token" value="<?php echo $token;?>"/>
<input type="hidden" name="form_name" value="<?php echo $formname;?>"/>
</form>
</fieldset>
