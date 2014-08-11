<?php
// wcf imports
require_once (WCF_DIR . 'lib/system/event/EventListener.class.php');

/**
 * Checks a new post for links to imagehosters and throws an error if any is
 * found
 *
 * @author Andreas Diendorfer
 * @copyright 2012 Andoca Haustier-WG UG
 *           
 */
class ImageHostBlockerEventListener implements EventListener {

	/**
	 *
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		// check if the user is excluded from this plugin
		
		if (WCF::getUser()->getPermission('user.board.excludeFromIhb')) {
			return;
		}
		
		// regexp to check if there is a link in the message
		$pattern = '#(?<!\B|=|"|\'|,|\|/]|\?)(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#ix';
		if (!preg_match_all($pattern, $eventObj->text, $matches)) {
			return; // no links in this message, no need to check any further
		}
		
		// build a blacklist from the settings text array
		// $blacklist = ArrayUtil::trim(explode("\n", trim(IHB_BLACKLIST)));
		$blacklist = trim(str_replace("\n", ', ', IHB_BLACKLIST));
		
		// check if there is a link in the string that is not whitelisted
		$foundLinks = array ();
		foreach ($matches [0] as $match) {
			$host = $this->get_domain($match);
			
			if (preg_match("#" . $host . "#i", $blacklist) && $host)
				$foundLinks [] = parse_url($match, PHP_URL_HOST);
		}
		
		// we have not found a blacklisted link
		if (!count($foundLinks))
			return;
		
		WCF::getTPL()->assign(array (
				'foundImageHosters' => $foundLinks 
		));
		
		WCF::getTPL()->append(array (
				'userMessages' => WCF::getLanguage()->get('de.andoca.ihb.imagehostfound') 
		));
		
		throw new UserInputException("text");
	}

	public function get_domain($url) {
		$pieces = parse_url($url);
		$domain = isset($pieces ['host']) ? $pieces ['host'] : '';
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
			return $regs ['domain'];
		}
		return false;
	}
}
?>