<?php

/*
 * $nggalbum    = $wpdb->prefix . 'gpxdata';

	// Create  table
	$sql = "CREATE TABLE gpxData (
	id BIGINT(20) NOT NULL AUTO_INCREMENT ,
	filename VARCHAR(255) NOT NULL ,
	
	post_id BIGINT(20)  ,
	post_url VARCHAR(255)  ,
	post_title VARCHAR(255)  ,
	post_excerpt VARCHAR(255)  ,
	post_featured_image VARCHAR(255)  ,
	
	total_distance_km VARCHAR(25) NOT NULL ,
	max_elevation_m VARCHAR(25) NOT NULL ,
	min_elevation_m VARCHAR(25) NOT NULL ,
	total_time VARCHAR(25) ,
	
	nggallery VARCHAR(25) ,
	post_category VARCHAR(255)  ,
	
	PRIMARY KEY  (id),
	KEY filename_key (filename)
	);;";
	
	//id,filename,post_id,post_url,post_title,post_excerpt,post_featured_image,total_distance_km,max_elevation_m ,min_elevation_m ,total_time,nggallery,post_category 
	
	//add in top
	require 'wp-gpx-maps-Maxwell.php';
	
	//remove wpgpxmaps_handle_folder_shortcodes()
	
	//add below line after print summary of wpgpxmaps_handle_shortcodes()
	maxUpSert($post,$gpx, $ngGalleries,$tot_len,$max_ele ,$min_ele ,$time_diff);
	
 * */

function maxUpSert($post,$filen, $ngGalleries,$total_distance_km,$max_elevation_m ,$min_elevation_m ,$total_time){
    if($post->post_status != 'publish'){
        return;
    }
    $filename=basename($filen);
    $post_id=$post->ID;
    $post_url=$post->guid;
    $post_title=$post->post_title;
    $post_excerpt=$post->post_excerpt;
    $post_featured_image=get_the_post_thumbnail_url($post);
    foreach (get_the_category($post_id) as $key => $value) {
        $catnames[] = $value->name;
    }
    $cat_name=implode(', ', $catnames);
    
    global $wpdb;
    $values=[$filename,$post_id,$post_url,$post_title,$post_excerpt,$post_featured_image,$total_distance_km,$max_elevation_m ,$min_elevation_m ,$total_time,$ngGalleries,$cat_name ];
    $sql="select count(1) from wp_gpxdata where filename='".$filename ."'";
    $insertSql= "INSERT INTO wp_gpxdata (filename,post_id,post_url,post_title,post_excerpt,post_featured_image,total_distance_km,max_elevation_m ,min_elevation_m ,total_time,nggallery,post_category ) VALUES ('" . implode( "','", $values ) ."') ";
    $updateSql="UPDATE wp_gpxdata set post_id ='".$post_id."',post_url ='".$post_url."',post_title ='".$post_title."',post_excerpt ='".$post_excerpt."',post_featured_image ='".$post_featured_image."',total_distance_km ='".$total_distance_km."',max_elevation_m='".$max_elevation_m."',min_elevation_m ='".$min_elevation_m."',nggallery ='".$ngGalleries."',post_category ='".$cat_name."' where  filename='".$filename ."'";
    if ($wpdb->get_var($sql )>0){
        if ( false === $wpdb->query($updateSql )) {
            return new WP_Error( 'db_insert_error', __( 'Could not insert term relationship into the database.' ), $wpdb->last_error );
        }
    }else{
        if ( false === $wpdb->query($insertSql )) {
            return new WP_Error( 'db_insert_error', __( 'Could not insert term relationship into the database.' ), $wpdb->last_error );
        }
    }
    
}

function maxGetData($filen){
    $filename=basename($filen);
    global $wpdb;
    $sql="select *  from wp_gpxdata where filename='".$filename ."'";
    $data=$wpdb->get_row($sql );
    if (!$data) {
        return '';
    }
    $output="<center><a target=_blank href='".$data->post_url."'><span class='normalfont'>".$data->post_title."</span><br><img src='".$data->post_featured_image."' width=397></a></center><br><table class='normalfont' width=397><tr><td valign=top align=left width=25%>Category:</td><td>".$data->post_category."</td></tr><tr><td>Distance:</td><td>".$data->total_distance_km."</tr><tr><tr><td>Max Elev:</td><td>".$data->max_elevation_m."</tr><tr><td valign=top>Min Elev:</td><td>".$data->min_elevation_m."</td></tr><tr><td valign=top>Duration:</td><td>".$data->total_time."</td></tr><tr><td valign=top align=left width=60></td><td>".preg_replace("/\r\n|\r|\n/", '<br/>', $data->post_excerpt)."</td></tr></table>";
    return $output;
}

function wpgpxmaps_handle_folder_shortcodes( $attr, $content = '' ) {
    
	$folder         = wpgpxmaps_findValue( $attr, 'folder', '', '' );
	$pointsoffset   = wpgpxmaps_findValue( $attr, 'pointsoffset', 'wpgpxmaps_pointsoffset', 10 );
	$distanceType   = wpgpxmaps_findValue( $attr, 'distanceType', 'wpgpxmaps_distance_type', 0 );
	$donotreducegpx = wpgpxmaps_findValue( $attr, 'donotreducegpx', 'wpgpxmaps_donotreducegpx', false );
	$uom            = wpgpxmaps_findValue( $attr, 'uom', 'wpgpxmaps_unit_of_measure', '0' );

	/* Fix folder path */
	$sitePath = wp_gpx_maps_sitePath();
	$folder   = trim( $folder );
	$folder   = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $folder );
	$folder   = $sitePath . $folder;

	$files = scandir( $folder );
	$points_maps       = '[';
	$popData       = '[';
	foreach ( $files as $file ) {

		if ( strtolower( substr( $file, - 4 ) ) == '.gpx' ) {

			$gpx    = $folder . DIRECTORY_SEPARATOR . $file;
			$pop=maxGetData($gpx);
			if($pop ==''){
			    continue;
			}
			$points = wpgpxmaps_getPoints_max( $gpx, $pointsoffset, false, $distanceType );
			$points_x_lat  = $points->lat;
			$points_x_lon  = $points->lon;
			
			
			$points_route='[';
			if ( is_array( $points_x_lat ) )
			foreach ( array_keys( $points_x_lat ) as $i ) {
				$_lat = (float) $points_x_lat[$i];
				$_lon = (float) $points_x_lon[$i];

				if ( 0 == $_lat && 0 == $_lon ) {
				    $points_route       .= 'null,';

				} else {
				    $points_route .= '[' . number_format( (float) $points_x_lat[$i], 7, '.', '' ) . ',' . number_format( (float) $points_x_lon[$i], 7, '.', '' ) . '],';
					$_dist = (float) $points->dist[$i];
					
					if ( '1' == $uom ) {
						/* feet / miles */
						$_dist *= 0.000621371192;

					} elseif ( '2' == $uom ) {
						/* meters / kilometers */
						$_dist = (float) ( $_dist / 1000 );

					} elseif ( '3' == $uom ) {
						/* meters / nautical miles */
						$_dist = (float) ( $_dist / 1000 / 1.852 );

					} elseif ( '4' == $uom ) {
						/* meters / miles */
						$_dist *= 0.000621371192;

					} elseif ( '5' == $uom ) {
						/* feet / nautical miles */
						$_dist = (float) ( $_dist / 1000 / 1.852 );
					}

				}
			}
			$points_route .='],';
			$points_maps.=$points_route;
			$popData.='"'.$pop.'",';
			//print_r( $points );
		}
	}
	$points_maps .=']';
	$popData .=']';
	
	global $post;
	$r = $post->ID . '_' . rand( 1,5000000 );
	$w              = wpgpxmaps_findValue( $attr, 'width', 'wpgpxmaps_width', '100%' );
	$mh             = wpgpxmaps_findValue( $attr, 'mheight', 'wpgpxmaps_height', '450px' );
	$gh             = wpgpxmaps_findValue( $attr, 'gheight', 'wpgpxmaps_graph_height', '200px' );
	$mt                 = wpgpxmaps_findValue( $attr, 'mtype', 'wpgpxmaps_map_type', 'HYBRID' );
	$uomspeed          = wpgpxmaps_findValue( $attr, 'uomspeed', 'wpgpxmaps_unit_of_measure_speed', '0' );
	$ngGalleries = wpgpxmaps_findValue( $attr, 'nggalleries', 'wpgpxmaps_map_ngGalleries', '' );
	$ngimgs_data = '';
	$error = '';
	$output = '
		<div id="wpgpxmaps_' . $r . '" class="wpgpxmaps">
			<div id="map_' . $r . '_cont" style="width:' . $w . '; height:' . $mh . ';position:relative" >
				<div id="map_' . $r . '" style="width:' . $w . '; height:' . $mh . '"></div>
				<div id="wpgpxmaps_' . $r . '_osm_footer" class="wpgpxmaps_osm_footer" style="display:none;"><span> &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors</span></div>
			</div>
			<canvas id="myChart_' . $r . '" class="plot" style="width:' . $w . '; height:' . $gh . '"></canvas>
			<div id="ngimages_' . $r . '" class="ngimages" style="display:none">' . $ngimgs_data . '</div>
			<div id="report_' . $r . '" class="report"></div>
		</div>
		' . $error . '
		<script type="text/javascript">
		    
			jQuery(document).ready(function() {
		    
				jQuery( "#wpgpxmaps_' . $r . '" ).wpgpxmaps( {
					targetId           : "' . $r . '",
					mapType            : "' . $mt . '",
					mapData            : ' . $points_maps . ',
                    popData            : ' . $popData . ',
					graphDist          : [],
					graphEle           : [],
					graphSpeed         : [],
					graphHr            : [],
					graphAtemp         : [],
					graphCad           : [],
					graphGrade         : [],
					waypoints          : [],
					unit               : "' . $uom . '",
					unitspeed          : "' . $uomspeed . '",
					color1             : ["#3366cc"],
					color2             : ["#3366cc"],
					color3             : ["#ff0000"],
					color4             : ["#ff77bd"],
					color5             : "#beecff",
					color6             : "#beecff",
					color7             : "#ff77bd",
					chartFrom1         : "",
					chartTo1           : "",
					chartFrom2         : "",
					chartTo2           : "",
					startIcon          : "",
					endIcon            : "",
					currentIcon        : "",
					waypointIcon       : "",
					currentpositioncon : "",
					usegpsposition     : "true",
					zoomOnScrollWheel  : "true",
					ngGalleries        : [' . $ngGalleries . '],
					ngImages           : [],
					pluginUrl          : "' . plugins_url() . '",
					TFApiKey           : "' . get_option( 'wpgpxmaps_openstreetmap_apikey' ) . '",
					langs              : {
						altitude        : "' . __( 'Altitude', 'wp-gpx-maps' ) . '",
						currentPosition : "' . __( 'Current position', 'wp-gpx-maps' ) . '",
						speed           : "' . __( 'Speed', 'wp-gpx-maps' ) . '",
						grade           : "' . __( 'Grade', 'wp-gpx-maps' ) . '",
						heartRate       : "' . __( 'Heart rate', 'wp-gpx-maps' ) . '",
						atemp           : "' . __( 'Temperature', 'wp-gpx-maps' ) . '",
						cadence         : "' . __( 'Cadence', 'wp-gpx-maps' ) . '",
						goFullScreen    : "' . __( 'Go full screen', 'wp-gpx-maps' ) . '",
						exitFullFcreen  : "' . __( 'Exit full screen', 'wp-gpx-maps' ) . '",
						hideImages      : "' . __( 'Hide images', 'wp-gpx-maps' ) . '",
						showImages      : "' . __( 'Show images', 'wp-gpx-maps' ) . '",
						backToCenter	: "' . __( 'Back to center', 'wp-gpx-maps' ) . '"
					}
				});
						    
			});
						    
		</script>';
	return $output;
}

function wpgpxmaps_getPoints_max( $gpxPath, $gpxOffset = 10, $donotreducegpx = false, $distancetype = 0) {
    
    $points = array();
    $dist   = 0;
    
    $lastLat    = 0;
    $lastLon    = 0;
    $lastEle    = 0;
    $lastOffset = 0;
    
    if ( file_exists( $gpxPath ) ) {
        $points = wpgpxmaps_parseXml( $gpxPath, $gpxOffset, $distancetype );
    } else {
        echo _e( 'WP GPX Maps Error: GPX file not found!', 'wp-gpx-maps' ) . ' ' . $gpxPath;
    }
    
    /* Reduce the points to around 200 to speedup */
    $pointsCount=100;
    if ( $donotreducegpx != true ) {
        $count = sizeof( $points->lat );
        if ( $count > $pointsCount ) {
            $f = round( $count/$pointsCount );
            if ( $f > 1 )
                for ( $i = $count; $i > 0;$i-- )
                    if ( $i % $f != 0 && $points->lat[$i] != null ) {
                        unset( $points->dt[$i] );
                        unset( $points->lat[$i] );
                        unset( $points->lon[$i] );
                        unset( $points->ele[$i] );
                        unset( $points->dist[$i] );
                        unset( $points->speed[$i] );
                        unset( $points->hr[$i] );
                        unset( $points->atemp[$i] );
                        unset( $points->cad[$i] );
                        unset( $points->grade[$i] );
                    }
        }
    }
    return $points;
}