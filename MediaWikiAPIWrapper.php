<?php


require_once('APIInit.php');


/**
 * 
 * @author auzigog
 */
class MediaWikiAPIWrapper {
	public $oldcwd;

    public function __construct() {
		$mediawiki_root = '/Users/eyeRmonkey/www/mediawiki-test';
require_once('APIInit.php');
//		// Trick MW into thinking that we're running this from the proper root directory
//		$this->set_mw_root_directory($mediawiki_root);
//
//		// Run the code MW uses to get the API ready
//		$this->api_init($mediawiki_root);
		
		/* Construct an ApiMain with the arguments passed via the URL. What we get back
		 * is some form of an ApiMain, possibly even one that produces an error message,
		 * but we don't care here, as that is handled by the ctor.
		 */
		$processor = new ApiMain($wgRequest, $wgEnableWriteAPI);

		// Process data & print results
		$processor->execute();
	}

	public function __destruct() {
		$this->api_kill();
	}


	/**
	 * Modified version of api.php from mediawiki 1.13.3
	 */
	public function api_init($mediawiki_root) {

		// Initialise common code
		require ($mediawiki_root . '/includes/WebStart.php');

		wfProfileIn('api.php');

		// URL safety checks
		//
		// See RawPage.php for details; summary is that MSIE can override the
		// Content-Type if it sees a recognized extension on the URL, such as
		// might be appended via PATH_INFO after 'api.php'.
		//
		// Some data formats can end up containing unfiltered user-provided data
		// which will end up triggering HTML detection and execution, hence
		// XSS injection and all that entails.
		//
		// Ensure that all access is through the canonical entry point...
		//
		if( isset( $_SERVER['SCRIPT_URL'] ) ) {
			$url = $_SERVER['SCRIPT_URL'];
		} else {
			$url = $_SERVER['PHP_SELF'];
		}
		if( strcmp( "$wgScriptPath/api$wgScriptExtension", $url ) ) {
			wfHttpError( 403, 'Forbidden',
				'API must be accessed through the primary script entry point.' );
			return;
		}

		// Verify that the API has not been disabled
		if (!$wgEnableAPI) {
			echo 'MediaWiki API is not enabled for this site. Add the following line to your LocalSettings.php';
			echo '<pre><b>$wgEnableAPI=true;</b></pre>';
			die(-1);
		}

	}

	/**
	 * Set up some variables to trick mediawiki into thinking it's being run
	 * from the root directory.
	 * @param <type> $mw_root_directory
	 * @return <type>
	 */
	function set_mw_root_directory($mw_root_directory) {
		$GLOBALS['IP'] = $mw_root_directory;
		return putenv("MW_INSTALL_PATH=$mw_root_directory");
	}

	

	function api_kill() {
		// Execute any deferred updates
		wfDoUpdates();

		// Log what the user did, for book-keeping purposes.
		wfProfileOut('api.php');
		wfLogProfilingData();

		// Shut down the database
		wfGetLBFactory()->shutdown();
	}
}

function debug()
{
	$debug_array = debug_backtrace();
	$counter = count($debug_array);
	for($tmp_counter = 0; $tmp_counter != $counter; ++$tmp_counter)
	{
	  ?>
	  <table width="558" height="116" border="1" cellpadding="0" cellspacing="0" bordercolor="#000000">
		<tr>
		  <td height="38" bgcolor="#D6D7FC"><font color="#000000">function <font color="#FF3300"><?
		  echo($debug_array[$tmp_counter]["function"]);?>(</font> <font color="#2020F0"><?
		  //count how many args a there
		  $args_counter = count($debug_array[$tmp_counter]["args"]);
		  //print them
		  for($tmp_args_counter = 0; $tmp_args_counter != $args_counter; ++$tmp_args_counter)
		  {
			 echo($debug_array[$tmp_counter]["args"][$tmp_args_counter]);

			 if(($tmp_args_counter + 1) != $args_counter)
			 {
			   echo(", ");
			 }
			 else
			 {
			   echo(" ");
			 }
		  }
		  ?></font><font color="#FF3300">)</font></font></td>
		</tr>
		<tr>
		  <td bgcolor="#5F72FA"><font color="#FFFFFF">{</font><br>
			<font color="#FFFFFF">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;file: <?
			echo($debug_array[$tmp_counter]["file"]);?></font><br>
			<font color="#FFFFFF">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;line: <?
			echo($debug_array[$tmp_counter]["line"]);?></font><br>
			<font color="#FFFFFF">}</font></td>
		</tr>
	  </table>
	  <?
	 if(($tmp_counter + 1) != $counter)
	 {
	   echo("<br>was called by:<br>");
	 }
   }
	exit();
}
?>
