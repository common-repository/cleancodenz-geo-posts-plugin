<?php
/*
 Plugin Name: CleanCode NZ Geo Posts Plugin
 Plugin URI: http://www.cleancode.co.nz/cleancodenz-geo-posts-wordpress-plugin
 Description: A tool to enter posts with geo locations and list them on google map,function finding geo coordinates of a location is built in. 
 Version: 1.2.0
 Author: CleanCode NZ
 Author URI: http://www.cleancode.co.nz/about
 License: GPL2
 */

/*
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; version 2 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

$cleancodenz_gp_ver='1.2.0';

require_once('geoposts-config.php') ;
require_once('gp-meta-box.php');
// this is for options
require_once('cleancodenzgp-options.php');

/*
 * Plugin admin registration area
 * * */

/*
 * Register the cleancodenz_gp post type
*/
add_action('init', 'create_cleancodenz_gp_post_type' );
function create_cleancodenz_gp_post_type()
{
  if(is_admin())
  {
    $allgptypes = getGeoPostsTypes();
     
    foreach ($allgptypes as $gp)
    {
      $args = array(
          'labels' =>
          array('name' => $gp['name'] ,
              'add_new_item' =>'Add New '.$gp['singular_name'],
              'edit_item' =>'Edit '.$gp['singular_name'],
              'new_item' =>'New '.$gp['singular_name'],
              'view_item' =>'View '.$gp['singular_name'],
              'not_found' =>'No '.$gp['singular_name'].'s found',
              'not_found_in_trash' =>'No '.$gp['singular_name'].'s found in trash',
              'search_items' =>'Search '.$gp['singular_name'].'s',
              'singular_name' => $gp['singular_name']),
          'publicly_queryable' => true,
          'exclude_from_search' => true,
          'show_in_nav_menus' => false,
          'show_ui'  => true );

      register_post_type($gp['type'], $args);

      // to add columns to that type to be edited
      add_filter('manage_edit-'.$gp['type'].'_columns', 'add_cleancodenz_gp_columns');

      // to add the columns to that type to be shown
      add_action('manage_posts_custom_column', 'show_cleancodenz_gp_custom_column');
    }
    // to register editors
    $gpeditors = new GP_meta_boxes($allgptypes);

  }

}


/*
 * Register new image size
* */
function cleancodenz_add_google_markerimage_size() {
  //to add new marker image size
  add_image_size('CCNZMarkerImage',32,32, True);
}
add_action( 'after_setup_theme', 'cleancodenz_add_google_markerimage_size' );

/*
 * add the gp custom columns handler
*/

function add_cleancodenz_gp_columns($columns)
{
  global $post;

  $allgptypes = getGeoPostsTypes();


  foreach ($allgptypes as $gp)
  {
    if($gp['type']==$post->post_type)
    {
      foreach ($gp['fields'] as $field)
      {
        $columns[$field['id']] = $field['name'];
      }
    }
  }
   
  return $columns;
}

/*
 * show a custom column value (as shown in the admin list of geo posts)
* for geo posts just get the corresponding geo post meta data.
*/
function show_cleancodenz_gp_custom_column($name)
{
  global $post;

  $allgptypes = getGeoPostsTypes();


  foreach ($allgptypes as $gp)
  {
    if($gp['type']==$post->post_type)
    {
      foreach ($gp['fields'] as $field)
      {
        if($field['id']==$name)
        {
          echo get_post_meta($post->ID, $name, true);
        }
      }
    }
  }

}


/*
 * get the geo post meta data from name
*/
function getGPMetaOfName($gpname)
{
  $allgptypes = getGeoPostsTypes();

  foreach ($allgptypes as $gp)
  {
    if($gp['name']==$gpname)
    {
      return $gp;
    }
  }


}



/*
 * get the geo posts of a special type, returns an array
* $gpname: string,
* $conditions:field and value pairs array, array(
    *                  array('field' =>'fieldname','value' ='fieldvalue'),
    *                  array('field' =>'fieldname','value' ='fieldvalue'),
    * )
*/
function getGPOfName($gpname,$conditions=null)
{
  global $post;
  $geoposts = array();

  $gpmeta = getGPMetaOfName($gpname);
  if(isset($gpmeta))
  {
    $gp_query = new WP_Query(array('post_type' => $gpmeta['type'],
    'posts_per_page' => -1));

    if($gp_query->have_posts())
    {
      // a flag to indicate if this needs to be output
      $includethis = false;

      while ($gp_query->have_posts())
      {
        $gp_main = $gp_query-> next_post();

        // to get the meta data
        $geopost = array();
        $geopost['title'] = $gp_main->post_title;
        $geopost['content'] =  $gp_main->post_content;
        $geopost['type'] = $gp_main->post_type;

        $includethis = false;


        // to get the custom fields
        foreach($gpmeta['fields'] as $field)
        {
          $geopost[$field['id']] = get_post_meta($gp_main->ID, $field['id'], true);

          if(isset($conditions) && sizeof($conditions))
          {
            foreach($conditions as $keyvaluepair)
            {

              if ($keyvaluepair['field'] == $field['id']
              && $keyvaluepair['value'] == $geopost[$field['id']])
              {
                $includethis = true;
                break;
              }

            }
          }
          else
          {
            $includethis = true;
          }
        }

        if($includethis === true)
        {
          $geoposts[]=$geopost;
        }
      }
    }

     
  }

  return $geoposts;
}


add_action('wp_print_styles', 'cleancodenz_gp_map_styles');

function cleancodenz_gp_map_styles(){

  $page_title = get_option('cleancodenzgeop_map_page_title');
  if (is_page($page_title))
  {
    $myStyleFile =  plugins_url( 'geoposts.css', __FILE__ ) ;

    wp_enqueue_style( 'cleancodenz_gp_StyleSheets',$myStyleFile,false,'1.0');

  }
}


// add wp ascripts as this needs google maps
add_action('wp_print_scripts', 'cleancodenz_gp_map_headscripts');

function cleancodenz_gp_map_headscripts(){
  $page_title = get_option('cleancodenzgeop_map_page_title');
  if (is_page($page_title))
  {
    wp_enqueue_script('cleancodenz_gp_map_js','http://www.google.com/jsapi?autoload={"modules":[{name:"maps",version:3,other_params:"sensor=false"}]}');
  }
}


/*
 * check this page is the map page before doing anyting
*/
add_action('wp_head', 'load_cleancodenz_gp_map_init');

function load_cleancodenz_gp_map_init()
{
  $page_title = get_option('cleancodenzgeop_map_page_title');
  if (is_page($page_title))
  {
    $default_lat = get_option('cleancodenzgeop_default_lat');
     
    $default_lon =get_option('cleancodenzgeop_default_long');

    $default_zoom = get_option('cleancodenzgeop_default_zoom');

    if(isset($_POST['default_lat']) &&
        $_POST['default_lat'] !='' &&
        isset($_POST['default_lon']) &&
        $_POST['default_lon'] !='' )
    {
      $default_lat =  $_POST['default_lat'];
      $default_lon =  $_POST['default_lon'];
         
    }
    else
    {
      if (!isset($default_lat) ||$default_lat=='')
      {
        $default_lat = '-43.5320544';
      }
      
      if (!isset($default_lon) ||$default_lon=='')
      {
        $default_lon = '172.6362254';
      }
      
      if(!isset($default_zoom) || $default_zoom=='')
      {
        $default_zoom = 12;
      }
    }
    ?>

<script type="text/javascript">
            var map;
            var markersArray = [];

            var latEle;
            var lngEle;
            var geocoder;
            
            var catsarray = [];
            var allgpArray = [];

          
        	   
            function init() {
            	  var mapDiv = document.getElementById('map-canvas');
            	  map = new google.maps.Map(mapDiv,
            		{
            		  center: new google.maps.LatLng(<?php echo $default_lat ?>, <?php echo $default_lon ?>),
            		  zoom: <?php echo $default_zoom ?>,
            	      mapTypeId: google.maps.MapTypeId.ROADMAP
            	     });

            	  google.maps.event.addListener(map,'center_changed',centerchanged);

            	  latEle = document.getElementById('default_lat');

            	  lngEle = document.getElementById('default_lon');
            	  // initialize
            	  latEle.value = <?php echo $default_lat ?>;
                  lngEle.value = <?php echo $default_lon ?>;
            	  
            	  geocoder = new google.maps.Geocoder();

            	  loadallcategory();
            	  //to load all
                  category_click(0);
            }

            google.maps.event.addDomListener(window, 'load', init);

            function addmarker(gp,markersarray){
                var image = new google.maps.MarkerImage(gp.icon,
                // This marker is 20 pixels wide by 32 pixels tall.
                new google.maps.Size(32, 32),
                // The origin for this image is 0,0.
                new google.maps.Point(0,0),
                // The anchor for this image is the base of the flagpole at 0,32.
                new google.maps.Point(0, 32));
             
                // Creating a marker
                var marker = new google.maps.Marker({
                	  position: new google.maps.LatLng(gp.lat, gp.lon),
                	  map: map,
                	  icon: image,
                	  title: gp.title
                });
             

             // Creating an InfoWindow object
              var infowindow = new google.maps.InfoWindow({
            	  content: gp.content
            	  });


              google.maps.event.addListener(marker, 'click', function() {
            	  infowindow.open(map, marker);
            	 });

              google.maps.event.addListener(marker, 'mouseout', function() {
            	  if (infowindow)
            	  {
            		  infowindow.close();
            	  }
            	});
             
               markersarray.push(marker);
            }

            function addgpstocategories(catindex,title,content,lat,lon,image)
            {
            	  var newgp = new Object();
            	  newgp.lat = lat;
            	  newgp.lon = lon;
            	  newgp.title =title;
            	  newgp.content = content;
            	  if(image)
                  {
            		  newgp.icon = image;
            	   }
            	  else
            	  {
            		  newgp.icon = catsarray[catindex].icon;
            	   }
            	  allgpArray[catindex].push(newgp);
             }

            function addcategories(name,icon)
            {
            	  var newcat = new Object();
            	  newcat.name = name;
            	  newcat.icon = icon;
            	  catsarray.push(newcat);
             }

            function category_click(category)
            {
            	  cleararray();

            	  if(category==0)
                  {
            		  //dsiplay all
                      for (i in allgpArray) {
                    	  addgpoverlay(allgpArray[i]);
                       }
                   }
            	  else
            	  {
            		  //only that category
                      addgpoverlay(allgpArray[category]);
                   }
             
            	  //handle it in ui
                  marklegend(category);

                  return false;
             }

            function cleararray()
            {
            	  if (markersArray) {
            		  for (i in markersArray) {
            			  markersArray[i].setMap(null);
            		  }
            		  markersArray.length = 0;
            	}

           }

            function addgpoverlay(categorizedgpsarray)
            {
            	   if (categorizedgpsarray) {
            		   for (i in categorizedgpsarray) {
            			   addmarker(categorizedgpsarray[i],markersArray);
            			}
            		}
             }

            function marklegend(category)
            {
            	  for (i=0;i<allgpArray.length;i++){
                	  var clegend = document.getElementById('map-legend-'+i);
                	  if (clegend)
                	  {
            		    clegend.className=" ";
                	  }
            		 }

          		 var selectedclegend = document.getElementById('map-legend-'+category);
          		 if(selectedclegend)
          		 {
          			selectedclegend.className='selectedlegend';
          		 }
            }

            function centerchanged()
            {
                 var mapcenter = map.getCenter();
                
            	 latEle.value = mapcenter.lat().toString();
                 lngEle.value = mapcenter.lng().toString();
              
            }

            function loadsearchadd()
            {
                var address = document.getElementById('default_location').value;
                
                if(address!=null && address !="")
                {
                
                    geocoder.geocode({'address': address}, function (results, status) {

                    if (status == google.maps.GeocoderStatus.OK) {
                        map.setCenter(results[0].geometry.location);

                        centerchanged();
                        
                    } else {
                        alert("Geocode was not successful for the following reason: " + status);
                    }});
                }

               
            }
</script>

<?php


  }

}
/*
 * Get the id matching category of a geo posts
* */
function getCatIndex($cats,$catname)
{
  $index =0;

  $i=0;
  foreach ($cats as $cat)
  {
    if($cat['name']==$catname)
    {
      $index =$i;
      break;
    }
    $i++;
  }

  return $index;
}

/*
 * get the geo posts for display on google map  div: map-canvas on the map page.
*/
function cleancodenz_gp_map_generate()
{
  // to get all geo posts
  $gps = getGPOfName('Geo Posts');

  $cats = getAllCategories();


  if(isset($gps))
  {
    //plot map legend

    ?>
<div>
<div id="map-legend">
<div>
<input type="hidden" id="default_lat" name="default_lat" value="<?php echo $_POST['default_lat'];  ?>">
<input type="hidden" id="default_lon" name="default_lon" value="<?php echo $_POST['default_lon'];  ?>">
<input type="hidden" id="default_zoom" name="default_zoom" value="<?php echo $_POST['default_zoom'];  ?>">
</div>
<?php
$i = 0;
foreach ($cats as $cat)
{

?>
<span id="map-legend-<?php echo $i ?>"><input type="image"
src="<?php echo $cat['icon'];?>" name="image"
onclick="category_click(<?php echo $i; ?>)" /> <?php echo $cat['name']; ?>
</span>
<?php
$i++;
}// end of ach cat
?>
</div>
<div id="map-canvas"></div>
</div>

<?php
//plot markers
echo "<script type=\"text/javascript\"> \n";

echo "function loadallcategory() { \n";

// to set cat array
foreach ($cats as $cat)
{
?>
addcategories(
<?php echo gp_javascriptstr_escape($cat['name']); ?>
,
<?php echo gp_javascriptstr_escape($cat['icon']);?>
); var gpofcategory = []; allgpArray.push(gpofcategory);

<?php

}

foreach($gps as $gp)
{
if(isset($gp['gp_1_lat']) && isset($gp['gp_1_long']))
{
//to construct the content html
$content ='<div>'.
'<h1>'.$gp['title'] .'</h1>'.
'<div><img src="'.$gp['gp_1_image'] .'"/></div>'.
'<div>'.$gp['content'] .'</div>'.
'</div>' ;



?>
addgpstocategories(
<?php echo getCatIndex($cats,$gp['gp_1_category']); ?>
,
<?php echo gp_javascriptstr_escape($gp['title'])  ?>
,
<?php echo gp_javascriptstr_escape($content)  ?>
,
<?php echo $gp['gp_1_lat'] ?>
,
<?php echo $gp['gp_1_long']?>
,
<?php echo gp_javascriptstr_escape($gp['gp_1__marker_image']) ?>
);

<?php

} //   if(isset($gp['gp_1_lat']) && isset($gp['gp_1_long']))

} // foreach($gps as $gp)

echo "}\n";
echo ' </script>';
}
}

function gp_javascriptstr_escape($str)
{
//version 1, for latest php 5.3
  //return json_encode($str,JSON_HEX_APOS);

  // version 2

  return '"'.mysql_escape_string($str).'"';
  
  
}

/*
 * check this page is the map page before doing anyting
 */
add_action('the_content', 'load_cleancodenz_gp_map');

function load_cleancodenz_gp_map($content)
{

  $page_title = get_option('cleancodenzgeop_map_page_title');
  if (is_page($page_title))
  {
    echo $content;
    cleancodenz_gp_map_generate();
    return '';

  }
  return $content;
}




