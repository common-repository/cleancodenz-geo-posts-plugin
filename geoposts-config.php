<?php
function getGeoPostsTypes()
{
  return array(
  /*
   * Geo Posts
   * */
  array(
        'id' => 1,
        'name' => 'Geo Posts',//menu item
        'singular_name' => 'Geo Post',
        'fields' => array( // excludes the content which is inherited from post
  array(
                            'name' => 'Image',
                            'id' => 'gp_1_image',
                            'desc' => 'Enter an URL or upload an image',
                            'type' => 'image' // image upload
  ),
  array(
                            'name' =>  'Location',
                            'desc' => 'Location,click next button to find geo coordinates for this location',
                            'id' => 'gp_1_location',
                            'type' => 'geolocation', // text box
                             'std' => '' //
  ),
  array(
                            'name' =>  'Lat',
                            'desc' => 'Lattitude',
                            'id' => 'gp_1_lat',
                            'type' => 'geolatitude', // text box
                            'std' => ''
                            ),
                            array(
                            'name' =>  'Long',
                            'desc' => 'Longitutde',
                            'id' => 'gp_1_long',
                            'type' => 'geolongitude', // text box
                            'std' => ''
                            ),
                            array(
                            'name' =>  'Category',
                            'desc' => 'Category',
                            'id' => 'gp_1_category',
                            'type' => 'select', 
                            'options' => 'getAllCategoryNames'  // a callback that returns an array                         )
                            ),
                            array(
                            'name' => 'Marker Image',
                            'id' => 'gp_1__marker_image',
                            'desc' =>'Enter an URL or upload an image for marker loverlay',
                            'type' => 'markerimage' // image upload
  )
),
        'type' => 'cleancodenz_gp'
        )
        );
}

function getAllCategories()
{
   $dir = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
   
  return Array(
   /*category def*/
   array(
        'name'=>'All Categories',
        'description' =>'Not specific, but to display with all others for all ', 
        'icon'=>$dir.'/cat0.png' 
   ),
   array(
        'name'=>'Category 1',
        'description' =>'Category 1 description', 
        'icon'=>$dir.'/cat1.png' 
   ),
    array(
        'name'=>'Category 2',
        'description' =>'Category 2 description', 
        'icon'=>$dir.'/cat2.png' 
   ),
    array(
        'name'=>'Category 3',
        'description' =>'Category 3 description', 
        'icon'=>$dir.'/cat3.png' 
   )
   
  );
  
}

function getAllCategoryNames()
{
  $allcats = getAllCategories();
  
  $names = array();
  
  foreach ($allcats as $cat)
  {
    $names[]=$cat['name'];  
  }
  return $names;
}

