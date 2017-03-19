<?php
/**
 * @author magog
 *
 */
class Cleanup_OTRS implements Cleanup_Module {
	
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->preg_replace(
			"/\{\{\s*PermissionOTRS\s*\|\s*(?:https?:)?\/\/ticket\.wikimedia\.org\/otrs\/index\.pl\?Action\s*\=\s*AgentTicketZoom&(?:amp;)?TicketNumber\=(\d+)\s*\}\}/u",
			"{{PermissionOTRS|id=$1}}");
		$ci->preg_replace(
			"/\{\{\s*PermissionOTRS\s*\|\s*(?:https?:)?\/\/ticket\.wikimedia\.org\/otrs\/index\.pl\?Action\s*\=\s*AgentTicketZoom&(?:amp;)?TicketID\=(\d+)\s*\}\}/u",
			"{{PermissionOTRS|ticket=https://ticket.wikimedia.org/otrs/index.pl?Action=AgentTicketZoom&TicketID=$1}}");
		
	}
}