<?php

/**
 * 
 * @author magog
 */
abstract class Latitude_Longitude_Svg_Creator {
	
	/**
	 *
	 * @var string
	 */
	private $color = "#0000ff";
	
	/**
	 *
	 * @var string valid values are: svg, png
	 */
	private $extension = "svg";
	
	/**
	 *
	 * @var float
	 */
	private $fill_radius = 1;
	
	/**
	 *
	 * @var Latitude_Longitude_Svg_Internal_Get_Result
	 */
	private $lat_longs;
	
	/**
	 * The argument key for $_REQUEST args
	 * @return string
	 */
	public static abstract function get_arg_key();
	
	/**
	 * 
	 * @param string[] $args
	 * @return self
	 */
	public static abstract function load_from_request_args(array $args);
	
	/**
	 *
	 * @return Latitude_Longitude_Svg_Internal_Get_Result
	 */
	protected abstract function get_latitude_longitudes();
	
	/**
	 *
	 * @param Latitude_Longitude_Svg $svg        	
	 * @return string The new SVG text
	 * @throws IllegalStateException
	 */
	public final function run(Latitude_Longitude_Svg $svg) {
		global $logger;
		
		$logger->debug("run(" . $svg->get_name() . ")");
		
		$this->load_and_cache();
		
		$fill_radius = $this->fill_radius * $svg->get_height() / 3600;
		$new_lines = array_map_filter($this->lat_longs->latitude_longitudes, 
			function (Latitude_Longitude $ll) use($svg, $fill_radius) {
				if (self::is_off_map($ll, $svg)) {
					return;
				}
				
				$next_fill_radius = $fill_radius * sqrt($ll->count);
				
				$x = ($ll->longitude - $svg->get_west()) * $svg->get_width() +
					 $svg->get_viewbox_west();
				$y = $svg->get_viewbox_north() -
					 ($ll->latitude - $svg->get_south()) * $svg->get_height();
				
				return sprintf(
					"<svg:path type=\"arc\" style=\"fill:%s;stroke-width:0\" d=\"M %f,%f A %f," .
						 "%f 0 1 1 %f,%f A %f,%f 0 1 1 %f,%f z\" />\n", 

						$this->color, $x + $next_fill_radius, $y, $next_fill_radius, 
						$next_fill_radius, $x - $next_fill_radius, $y, $next_fill_radius, 
						$next_fill_radius, $x + $next_fill_radius, $y);
			});
		
		$end = strripos($svg->get_text(), "</svg");
		if ($end === false) {
			throw new IllegalStateException("Can't find end of SVG for " . $svg->get_name());
		}
		
		$new_text = substr($svg->get_text(), 0, $end) .
			 "<svg:g\nid=\"locationdots\"\nstyle=\"stroke-miterlimit:4;stroke-dasharray:none\">\n" .
			 join("", $new_lines) . "</svg:g>" . substr($svg->get_text(), $end);
		
		return $new_text;
	}
	
	/**
	 *
	 * @return Latitude_Longitude_Which_Result
	 */
	public function which() {
		// find points unrendered on any SVG
		$this->load_and_cache();
		
		$unrendered = $this->lat_longs->latitude_longitudes;
		$svgs = Latitude_Longitude_Svg::load();
		array_walk($svgs, 
			function (Latitude_Longitude_Svg $svg) use(&$unrendered) {
				$unrendered = array_filter($unrendered, 
					function (Latitude_Longitude $ll) use($svg) {
						return self::is_off_map($ll, $svg);
					});
			});
		
		// which SVGs?
		$svgs = $this->which_internal();
		
		$warnings = array_merge($this->lat_longs->warnings, 
			array_map(
				function (Latitude_Longitude $lat_long) {
					global $logger;
					
					$error = "Unrendered map point: [$lat_long->latitude, $lat_long->longitude]";
					$logger->error($error);
					
					return $error;
				}, $unrendered));
		return new Latitude_Longitude_Which_Result($svgs, $warnings, $this->lat_longs->messages, 
			array_sum(
				array_map(
					function (Latitude_Longitude $lat_long) {
						return $lat_long->count;
					}, $this->lat_longs->latitude_longitudes)));
			
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_color() {
		return $this->color;
	}
	
	/**
	 *
	 * @param string $color        	
	 * @return void
	 */
	public function set_color($color) {
		global $validator;
		$validator->validate_arg($color, "string");
		
		$this->color = $color;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_fill_radius() {
		return $this->fill_radius;
	}
	
	/**
	 *
	 * @param int $fill_radius        	
	 * @return void
	 */
	public function set_fill_radius($fill_radius) {
		global $validator;
		$validator->validate_arg($fill_radius, "numeric");
		
		$this->fill_radius = $fill_radius;
	}
	
	/**
	 *
	 * @return Latitude_Longitude_Svg[]
	 */
	private function which_internal() {
		return array_filter(Latitude_Longitude_Svg::load(), 
			function (Latitude_Longitude_Svg $svg) {
				return array_search_callback($this->lat_longs->latitude_longitudes, 
					[self::class, "is_on_map"], false, false, $svg);
			});
	}
	
	/**
	 *
	 * @param Latitude_Longitude $ll        	
	 * @param Latitude_Longitude_Svg $svg        	
	 * @return boolean
	 */
	private static function is_off_map(Latitude_Longitude $ll, Latitude_Longitude_Svg $svg) {
		return !self::is_on_map($ll, null, $svg);
	}
	
	/**
	 *
	 * @param Latitude_Longitude $ll        	
	 * @param mixed $key_ignored        	
	 * @param Latitude_Longitude_Svg $svg        	
	 * @return boolean
	 */
	public static function is_on_map(Latitude_Longitude $ll, $key_ignored, 
		Latitude_Longitude_Svg $svg) {
		return $ll->longitude > $svg->get_west() && $ll->longitude < $svg->get_east() &&
			 $ll->latitude > $svg->get_south() && $ll->latitude < $svg->get_north();
	}
	
	/**
	 * Load longitudes/latitudes, and cache them locally for later use
	 *
	 * @return void
	 */
	public final function load_and_cache() {
		if ($this->lat_longs === null) {
			$this->lat_longs = $this->get_latitude_longitudes();
		}
	}
}