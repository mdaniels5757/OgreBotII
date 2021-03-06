<?php

/*
This file is part of Peachy MediaWiki Bot API

Peachy is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class Diff {

	/**
	 * Generates a diff between two strings
	 * 
	 * @param string $method Which style of diff to generate: unified, inline (HTML), context, threeway
	 * @param string $diff1 Old text
	 * @param string $diff2 New text
	 * @param string $diff3 New text #2 (if in three-way mode)
	 * @return string Generated diff
	 * @link http://pear.php.net/package/Text_Diff/redirected
	 * @package Text_Diff
	 */
	public static function load($method, $diff1, $diff2, $diff3 = null) {
		switch (strtolower($method)) {
			case 'unified':
				$diff = new Text_Diff('auto', array(explode("\n",$diff1), explode("\n",$diff2)));
	
				$renderer = new Text_Diff_Renderer_unified();
				
				$diff = $renderer->render($diff);
				break;
			case 'html':
			case 'inline':
				$diff = new Text_Diff('auto', array(explode("\n",$diff1), explode("\n",$diff2)));
	
				$renderer = new Text_Diff_Renderer_inline();
				
				$diff = $renderer->render($diff);
				break;
			case 'context':
				$diff = new Text_Diff('auto', array(explode("\n",$diff1), explode("\n",$diff2)));
	
				$renderer = new Text_Diff_Renderer_context();
				
				$diff = $renderer->render($diff);
				break;
			case 'threeway':
				$diff = new Text_Diff3( explode("\n",$diff1), explode("\n",$diff2), explode("\n",$diff3) );
				$diff = implode( "\n", $diff->mergedOutput() );
				$rendered = null;
		}
		unset($renderer);
		return $diff;
	}
}
