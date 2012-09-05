<?php

# Plugin Name: Last.Fm Records
# Version: 1.4.1
# Plugin URI: http://jeroensmeets.net/lastfmrecords/
# Description: The Last.Fm Records plugin lets you show what you are listening to, with a little help from our friends at last.fm.
# Author: Jeroen Smeets
# Author URI: http://jeroensmeets.net/

$_lfm_version = "1.4.1";

define('DEBUG', false);
define('LASTFM_APIKEY', 'fbfa856cc3af93c43359b57921b1e64e');

class LastFmRecords {

  private $albuminfo = array();
  # albuminfo is array with elements
  #   $albuminfo[0]['artist']                artist name, no url encoding
  #   $albuminfo[0]['title']                 cd title, no url encoding
  #   $albuminfo[0]['url']                   url to album page on last.fm
  #   $albuminfo[0]['coverimage']['small']   url to cd cover in three sizes
  #   $albuminfo[0]['coverimage']['medium']
  #   $albuminfo[0]['coverimage']['large']

  # lovedtracks is at the end of the array, which means it will not fall back on other periods
  private $periods = array('recenttracks', 'weekly', '3month', '6month', '12month', 'overall', 'lovedtracks');

  public function __construct() {
  }

  public function wordpressurl() {
    $_url = get_option('siteurl');
    if ("/" != substr($_url, -1)) {
      $_url .= "/";
    }
    return $_url;
  }

  public function getalbums($period = false, $count = false) {

    # let's start at the very beginning (a very good place to start)
    $this->log('start of function display');

    $options = get_option('lastfm-records');
    if ($period) {
      $options['period'] = $period;
    }
    if ($count) {
      $options['count'] = $count;
    }

    # TODO: determine if this is the best place to clean up the cache
    $this->cleanupcache($this->cachedir(), $options['username']);

    # get album info for this user
    $this->getalbuminfo($options);
    if (0 == count($this->albuminfo)) {
      $this->log('error in finding images');
	    echo $options['noimages'];
      return;
    }
	
	return $this->albuminfo;	
  }

  ################################################
  # this is the function that generates the html #
  ################################################

  public function display($period = false, $count = false) {

    # let's start at the very beginning (a very good place to start)
    $this->log('start of function display');

    $options = get_option('lastfm-records');
    if ($period) {
      $options['period'] = $period;
    }
    if ($count) {
      $options['count'] = $count;
    }

    # TODO: determine if this is the best place to clean up the cache
    $this->cleanupcache($this->cachedir(), $options['username']);

    # get album info for this user
    $this->getalbuminfo($options);
    if (0 == count($this->albuminfo)) {
      $this->log('error in finding images');
	    echo $options['noimages'];
      return;
    }

    echo "\n  <ol id=\"lastfmrecords\">\n";
    $_count = 0;

    foreach($this->albuminfo as $_album) {

      if ($_count == intval($options['count'])) {
        break;
      }

      # if ((($_count + 1) == intval($options['count'])) && ('onebig.css' == $options['display'])) {
      #   break;
      # }

      if ('' != $_album['coverimage']['medium']) {
      	$_coverImage = $_album['coverimage']['medium'];
      } else if ('' != $_album['coverimage']['large']) {
      	$_coverImage = $_album['coverimage']['large'];
      } else {
      	continue;
      }

      # TODO: there's more to explore?
      $_replace   = array("'", "&");
      $_replaceby = array("`", "&amp;");
      $_safeTitle  = str_replace($_replace, $_replaceby, $_album['artist']);
      $_safeArtist = str_replace($_replace, $_replaceby, $_album['title']);

?>
    <li>
      <a href='<?php echo $_album['url'] ?>'>
        <img class='cdcover cover<?php echo $_count + 1 ?>' src='<?php echo $_coverImage ?>' title='<?php echo $_safeArtist ?>: <?php echo $_safeTitle ?>' alt='cd cover' />
      </a>
    </li>
<?php
      $_count++;
    }
    global $_lfm_version;
?>
  </ol>
  <!-- Last.Fm Records <?php echo $_lfm_version ?> (<?php echo $options['username'] ?>, <?php echo $options['period'] ?> ?>) -->
<?php
  }

  # only add new cover images to the big list
  private function addalbumfound($_newAlbum) {
    $_inIt = false;
    foreach($this->albuminfo as $_album) {
      foreach($_album['coverimage'] as $_size => $_image) {
        if ($_image == $_newAlbum['coverimage'][$_size]) {
          $_inIt = true;
        }
      }
    }
    if (!$_inIt) {
    	$this->albuminfo[] = $_newAlbum;
    }
  }

  private function getalbuminfo($options) {
    # keep falling back to older data until enough albums are found
    if ('1' == $options['getmore']) {
      # with which period do we start?
      $currentperiod = 0;
      foreach ($this->periods as $periodkey => $periodname) {
        if ($periodname == $options['period']) {
          $currentperiod = $periodkey;
        }
      }
      while (($currentperiod <= count($this->periods)) && (count($this->albuminfo) < $options['count'])) {
        // echo $options['period'] . ':' . count($this->albuminfo) . '<hr />';
      	$this->getlist($options);
      	$this->log($options['period'] . ' made the album list ' . count($this->albuminfo) . ' items long.');

      	$currentperiod++;
      	$options['period'] = $this->periods[$currentperiod];
      }
    } else {
      $this->getlist($options);
    }
  }

  ####################################################################
  # get one feed from last.fm with cds that the user has listened to #
  ####################################################################

  private function getlist($options) {

    # where would the cached list be?
    $_cachefile = $this->cachefilename($options);

    if (file_exists($_cachefile)) {
      # cachefile exists
      $this->log($options['period'] . ' list is in cache');
      // ha, bug found! can't replace the whole array, have to add to it.
      // $this->albuminfo = unserialize(file_get_contents($_cachefile));
      $_cachelist = unserialize(file_get_contents($_cachefile));
      foreach($_cachelist as $_cachecd) {
        $this->addalbumfound($_cachecd);
      }
      return true;
    } else {
      # not cached, get list from last.fm and parse into an array
      $this->log('list is not in cache, get out and get it!');

      switch ($options['period']) {
        case 'recenttracks':
          $_last_fm_url = 'http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks'
                        . '&user=' . $options['username']
                        . '&limit=50'
                        . '&api_key=' . LASTFM_APIKEY;
          break;
        case 'lovedtracks':
          $_last_fm_url = 'http://ws.audioscrobbler.com/2.0/?method=user.getlovedtracks'
                        . '&user=' . $options['username']
                        . '&api_key=' . LASTFM_APIKEY;
          break;
        case 'weekly':
          $_last_fm_url = 'http://ws.audioscrobbler.com/2.0/?method=user.getWeeklyAlbumChart'
                        . '&user=' . $options['username']
                        . '&api_key=' . LASTFM_APIKEY;
          break;
        default:
          $_last_fm_url = 'http://ws.audioscrobbler.com/2.0/?method=user.gettopalbums'
                        . '&user=' . $options['username']
                        . '&period=' . $options['period']
                        . '&api_key=' . LASTFM_APIKEY;
      }

      $_result = $this->loadurl($_last_fm_url);
      if ($_result) {
        $this->parsexml($_result, $options);
        $this->writecachefile($this->albuminfo, $_cachefile);
      }
    }
  }

  ##############################################
  # parse xml that was returned by last.fm api #
  ##############################################

  private function parsexml($_html, $options) {
    if (!function_exists('simplexml_load_string')) {
    	$this->log('Sorry, you need PHP5 for this plugin');
      return false;
    }

    $_xml = $this->createsimplexml($_html);
    if (!$_xml) {
      return false;
    }

    # different elements in xml for different feeds
    switch ($options['period']) {
    	case 'recenttracks':
    	  $_elem0 = 'recenttracks';
    	  $_elem1 = 'track';
    	  break;
      case 'lovedtracks':
        $_elem0 = 'lovedtracks';
    	  $_elem1 = 'track';
        break;
      case 'weekly':
    	  $_elem0 = 'weeklyalbumchart';
    	  $_elem1 = 'album';
    	  break;
      default:
    	  $_elem0 = 'topalbums';
    	  $_elem1 = 'album';
    }

    foreach ($_xml->{$_elem0}->{$_elem1} as $_item) {
    	$_newItem = array();
      $_newItem['artist'] = (('recenttracks' == $options['period'])  || ('weekly' == $options['period']))
                          ? (string) $_item->artist
                          : (string) $_item->artist->name;
      $_newItem['title'] = ('recenttracks' == $options['period'])
                         ? (string) $_item->album
                         : (string) $_item->name;
      $_newItem['url'] = (string) $_item->url;
      $_newItem['coverimage'] = array();

      # image info is not in the weekly feed
      if ('weekly' == $options['period']) {
        $_images = $this->getimageinfo('album', (string) $_item->mbid);
        if ($_images) {
          $_newItem['coverimage'] = $_images;
          $this->addalbumfound($_newItem);
        }
      } else {
      	# check if urls are found for cover images
        $_imagefound = false;
        foreach($_item->image as $_image) {
          if (('' != (string) $_image) && (false === strpos((string) $_image, '/noimage/'))) {
          	$_imagefound = true;
          	$_imgsize = (string) $_image['size'];
            $_newItem['coverimage'][$_imgsize] = (string) $_image;
          }
        }
        # no covers found, we could try finding it when we have an mbid for the cd
        if (!$_imagefound) {
          $_album_mbid = (string) $_item->album['mbid'];
          if ('' != $_album_mbid) {
          	$_images = $this->getimageinfo('album', (string) $_item->mbid);
          	if ($_images) {
          		$_imagefound = true;
          		$_newItem['coverimage'] = $_images;
          	}
          }
        }
        if ($_imagefound) {
          $this->addalbumfound($_newItem);
        }
      }
    }

    return (count($this->albuminfo) > 0);
  }

  # images for an album or an artist
  private function getimageinfo($_type, $_mbid) {
    if (('artist' != $_type) && ('album' != $_type)) {
      return false;
    }

    if (!$_mbid) {
      return false;
    }

    $_last_fm_url = 'http://ws.audioscrobbler.com/2.0/?method=' . $_type . '.getinfo'
                  . '&mbid=' . $_mbid
                  . '&api_key=' . LASTFM_APIKEY;

    $_result = $this->loadurl($_last_fm_url);
    if (!$_result) {
      return false;
    }

    $_xml = $this->createsimplexml($_result);
    if (!$_xml) {
      return false;
    }

    $result = array();
    foreach($_xml->{$_type} as $_item) {
      foreach($_item->image as $_image) {
      	if (false === strpos((string) $_image, '/noimage/')) {
          $_imgsize = (string) $_image['size'];
          $result[$_imgsize] = (string) $_image;
        }
      }
      # TODO: what if there's more than one album/artist returned?
      break;
    }

    return $result;
  }

  private function createsimplexml($_html) {
    try {
      $_xml = @simplexml_load_string($_html);
    } catch (Exception $e) {
      $_xml = false;
    }
    	
    return (!$_xml) ? false : $_xml;
  }


  public function filtercontent($content) {
    while (false !== strpos($content, '[lastfmrecords')) {
      // extra options?
      $_period = false;
      $_count = false;
      if (false !== eregi('\[lastfmrecords\|([0-9a-z]+)\|([0-9]+)\]', $content, $regs)) {
      	$_period = $regs[1];
        $_count  = $regs[2];
      }

      # TODO: give $this->display() an option to return html
      ob_start();
      $this->display($_period, $_count);
      $result = ob_get_contents();
      ob_clean();
      $content = preg_replace('#\[lastfmrecords.*\]#', $result, $content, 1);
    }
    return $content;
  }

  #########################################################################
  # check if we already know where the image of the cd cover can be found #
  #########################################################################

  private function imageincache($_title, $_artist) {
    # please note that this function can return:
    # 1. an array with data for the url of the image
    # 2. 'noimage' (without the quotes, image is in cache, but no image was found)
    # 3. false: image is not in cache

    # is the image data already in the cache?
    $_cachefile = $this->cachefile_albumdata($_title, $_artist);
    if (file_exists($_cachefile)) {
  	  return unserialize(file_get_contents($_cachefile));
    } else {
  	  return false;
    }
  }

  #######################################################
  # load external image, create thumb and save to cache #
  # if one of these steps fails, return false           #
  #######################################################

  function createlocalthumb($_imagedata) {

    $_ext = pathinfo($_imagedata['image']);
    $_ext = strtolower($_ext['extension']);

    $_newname = base64_encode($_imagedata['artist'] . '#_#' . $_imagedata['cdtitle']) . '.' . $_ext;
    $_newurl  = $this->wordpressurl() . 'wp-content/plugins/last.fm/cache/' . base64_encode($_imagedata['artist'] . '#_#' . $_imagedata['cdtitle']) . '.' . $_ext;
    # does the thumb already exist?
    if (file_exists($_newname)) {
    	return $_newurl;
    }
    
    # do we have a bit of image support (PHP >= 4.3.0)?
    if (!function_exists('gd_info')) {
      return false;
    }

    # let's check if this image type is supported
    $_gdinfo = gd_info();

    # jpeg
    if (('jpg' == $_ext) || ('jpeg' == $_ext)) {
      if (!$_gdinfo['JPG Support']) {
        return false;
      }
    }

    # gif
    if ('gif' == $_ext) {
      if ((!$_gdinfo['GIF Read Support']) || (!$_gdinfo['GIF Create Support'])) {
        return false;
      }
    }

    # png
    if ('png' == $_ext) {
      if (!$_gdinfo['PNG Support']) {
        return false;
      }
    }
    
    # load image from external site
    $_i = $this->loadurl($_imagedata['image']);
    if (!$_i) {
      return false;
    }

    # TODO
    if (!function_exists('imagecreatefromstring')) {
      return false;
    }
    
    # try to create an image 'object'
    $_im = imagecreatefromstring($_i);
    if (!$_im) {
    	return false;
    }

    # try to resize it
    $options = $this->options();
    
    # if you didn't specify an image width, this won't work
    if (0 == $options['imgwidth']) {
      return false;
    }

    $_resized = imagecreatetruecolor($options['imgwidth'], $options['imgwidth']);
    if (!imagecopyresampled($_resized, $_im, 0, 0, 0, 0, $options['imgwidth'], $options['imgwidth'], imagesx($_im), imagesy($_im))) {
      # resizing failed
      return false;
    }

    # destroy original image
    imagedestroy($_im);

    # write new thumb to cache folder
    switch($_ext) {
    	case 'jpg':
    	case 'jpeg':
    	  if (imagejpeg($_resized,  $this->cachedir() . $_newname, 100)) {
    	    return $_newurl;
    	  }
    	  break;
    	case 'gif':
    	  if (imagegif($_resized, $this->cachedir() . $_newname)) {
    	    return $_newurl;
    	  }
    	  break;
    	case 'png':
    	  if (imagepng($_resized, $this->cachedir() . $_newname, 0)) {
    	    return $_newurl;
    	  }
    	  break;
    }
    
    return false;
  }

  ############################################
  # serialize $_array and write it to $_file #
  ############################################

  private function writecachefile($_array, $_file, $_append = false) {
    $_ser = serialize($_array);
    # write to cache
    if ($_append) {
      $_f = @fopen($_file, 'w');
    } else {
      $_f = @fopen($_file, 'w+');
    }
    if ($_f) {
      fwrite($_f, $_ser, strlen($_ser));
      fclose($_f);
    }
  }

  private function cleanupcache($_dir, $_lastfmusername) {
    # in theory, in multi user wordpress environments,
    # diffent blogs can display cd covers from the same
    # last.fm user
    
    # so we keep all cache files, except the ones for the current last.fm user that are not for today
    
    # TODO eventually: clean up really old album data?
    
    # this means when a user has cache files for "recenttracks", the cache will keep
    # 24 files before deleting them.
    if ($handle = @opendir($_dir)) {
      while (false !== ($_file = readdir($handle))) {
        # we skip everything that's not .list, as other files are cd data
        if (".list" == substr($_file, -5)) {
          # ok, it's a cached list. is it for the current last.fm user?
          if ($_lastfmusername == substr($_file, 0, strlen($_lastfmusername))) {
            # now, if it's not from today, we can delete it
            if (false === strpos($_file, "." . date("ymd"))) {
              @unlink($_dir . $_file);
            }
          }
        }
      }
    }
    
    # we're always happy
    return true;
  }

  private function loadurl($_url) {
    $_result = false;

    # added curl for Dreamhost etc.
    if (function_exists('curl_exec')) {
      $ch = curl_init();
      curl_setopt ($ch, CURLOPT_URL, $_url);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
      $_result = curl_exec($ch);
      curl_close($ch);
    } else {
      $fp = @fopen($_url, 'r');
      if ($fp) {
        $_result = "";
        while ($data = fgets($fp)) {
          $_result .= $data;
        }
        fclose($fp);
      }
    }

    return $_result;
  }

  private function cachefilename($options) {

    # this function returns
    # [lastfmname].[datepart].[period].list

    # refresh every hour for recent tracks
    $_datepart = ("recenttracks" == $options['period']) ? date("ymdH") : date("ymd");

    return $this->cachedir() . $options['username'] . "." . $_datepart . "." . $options['period'] . ".list";
  }

  private function cachefile_albumdata($_title, $_artist) {
    return $this->cachedir() . base64_encode($_artist . '#_#' . $_title) . '.albumdata';
  }

  private function cachedir() {
    # for reading from and writing to cache
    return dirname(__FILE__) . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR;
  }

  private function cleanuptext($_string) {
    # TODO we have to simplify this a bit ;-)
    return urldecode(str_replace(array("'", "%26"), array("`", "&"), urldecode($_string)));
  }

  public function options_page() {

    ######################################################################
    # direct calls to this script with a $_POST['ut'] and a $_POST['ua'] #
    # are an image upload for a missing cover                            #
    ######################################################################

    $_fadingstatus = false;
    if ((array_key_exists('ut', $_POST)) && (array_key_exists('ua', $_POST))) {
      if (!isset($_FILES['uf']['tmp_name'])) {
        $_fadingstatus = 1;
      } else {
        # let's restrict the images to jpg, gif and png
        $_ext = pathinfo($_FILES['uf']['name']);
        $_ext = $_ext['extension'];
        if (!in_array($_ext, array('jpg', 'gif', 'png'))) {
          $_fadingstatus = 2;
        } else {
          $_newname = base64_encode($_POST['ua'] . '#_#' . $_POST['ut']) . '.' . $_ext;
          if (!move_uploaded_file($_FILES['uf']['tmp_name'], $this->cachedir() . $_newname)) {
            $_fadingstatus = 1;
          } else {
      	    $_array = array('image'      => $this->wordpressurl() . 'wp-content/plugins/last.fm/cache/' . $_newname,
                            'cdtitle'    => $_POST['ut'],
                            'artist'     => $_POST['ua']
                           );
            $this->writecachefile($_array, $this->cachefile_albumdata($_POST['ut'], $_POST['ua']));
      	    $_fadingstatus = 4;
          }
        }
      }
    }

    # Get our options and see if we're handling a form submission.
    $options = get_option('lastfm-records');
    if (!is_array($options) ) {
      $options = array('title'      => 'last.fm records',
                       'username'   => '',
                       'count'      => '6',
                       'imgwidth'   => '85',
                       'noimages'   => 'No images to display',
                       'period'     => 'weekly');
    }

	  if (array_key_exists('lastfm-submit', $_POST)) {
      # $options['title']       = strip_tags(stripslashes($_POST['lastfm-title']));
      $options['username']    = strip_tags(stripslashes($_POST['lastfm-username']));
      $options['imgwidth']    = intval($_POST['lastfm-imgwidth']);
      if ($options['imgwidth'] < 10) {
    	  $options['imgwidth'] = 0;
      }

      $options['count']       = intval($_POST['lastfm-count']);
      if ($options['count'] < 1) {
        $options['count'] = 6;
      }

      $options['display']     = strip_tags(stripslashes($_POST['lastfm-display']));
      $options['stylesheet']  = strip_tags(stripslashes($_POST['lastfm-stylesheet']));
      $options['noimages']    = strip_tags(stripslashes($_POST['lastfm-noimages']));
      $options['period']      = strip_tags(stripslashes($_POST['lastfm-period']));
      $options['localthumbs'] = strip_tags(stripslashes($_POST['lastfm-localthumbs']));
      $options['getmore']     = strip_tags(stripslashes($_POST['lastfm-getmore']));

      update_option('lastfm-records', $options);

      $_fadingstatus = 3;
	  }

    # $_fadingstatus set?
    if ($_fadingstatus) {
      switch($_fadingstatus) {
        case 1:
          echo "<div id='message' class='updated fade'><p>File upload failed.</p></div>";
          break;
        case 2:
          echo "<div id='message' class='updated fade'><p>This file type is not supported.</p></div>";
          break;
        case 3:
          echo "<div id='message' class='updated fade'><p>The options for Last.Fm Records have been updated.</p></div>";
          break;
        case 4:
          echo "<div id='message' class='updated fade'><p>The cd cover has been uploaded!</p></div>";
          break;
      }
    }

    # html for options page
?>
<div class="wrap">
  <h2>Options for Last.fm Records</h2>
  <form method=post action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo strtolower(basename(__FILE__)); ?>">
    <input type="hidden" name="update" value="true">
    <fieldset class="options">
      <table class="optiontable"> 
        <tr valign="top"> 
          <th scope="row">last.fm username</th> 
          <td>
            <input name="lastfm-username" type="text" id="lastfm-username" value="<?php echo $options['username']; ?>" size="40" /><br />
            If you don't have a username, go get a free account at <a href="http://www.last.fm/" target="_blank">last.fm</a>.
          </td>
        </tr>
        <tr valign="top"> 
          <th scope="row">period</th>
          <td>
            <select style="width: 200px;" id="lastfm-period" name="lastfm-period">
              <option value="recenttracks"<?php if ('recenttracks' == $options['period']) { echo ' selected'; } ?>>recent tracks</option>
              <option value="weekly"<?php       if ('weekly'       == $options['period']) { echo ' selected'; } ?>>last week</option>
              <option value="3month"<?php       if ('3month'       == $options['period']) { echo ' selected'; } ?>>last 3 months</option>
              <option value="6month"<?php       if ('6month'       == $options['period']) { echo ' selected'; } ?>>last 6 months</option>
              <option value="12month"<?php      if ('12month'      == $options['period']) { echo ' selected'; } ?>>last 12 months</option>
              <option value="overall"<?php      if ('overall'      == $options['period']) { echo ' selected'; } ?>>give me everything</option>
              <option value="lovedtracks"<?php  if ('lovedtracks'  == $options['period']) { echo ' selected'; } ?>>loved tracks</option>
            </select><br />
            Last.fm provides summarized data over several periods. You can select the period here.<br /><br />
            'loved tracks' is new in version 1.4: in the last.fm client is a big heart to express your love for a track. If you do, the cd's will show up here.
          </td>
        </tr>
        <tr valign="top"> 
          <th scope="row">add stylesheet</th> 
          <td>
            <select style="width: 200px;" id="lastfm-stylesheet" name="lastfm-stylesheet">
              <option value="1"<?php if ('1' == $options['stylesheet']) { echo ' selected'; } ?>>Yes</option>
              <option value="0"<?php if ('0' == $options['stylesheet']) { echo ' selected'; } ?>>No</option>
            </select><br />
            You can set this to No if you want to do all the stylesheet work yourself.
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">image count</th>
          <td>
            <input name="lastfm-count" type="text" id="lastfm-count" value="<?php echo $options['count']; ?>" size="10" /><br />
            The maximum of cd covers to display
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">get more images<br />if necessary</th>
          <td>
            <select style="width: 200px;" id="lastfm-getmore" name="lastfm-getmore">
              <option value="0"<?php if ('0' == $options['getmore']) { echo ' selected'; } ?>>No</option>
              <option value="1"<?php if ('1' == $options['getmore']) { echo ' selected'; } ?>>Yes</option>
            </select><br />
            If you set this option to Yes, the plugin will look for more images from other periods if not enough images have been found.<br />
            For example, if Period is set to 'Recent tracks' and you are on holiday, the plugin will find cds you listened to last week or in the
            last three months to get enough images on your page.
          </td>
        </tr>
        <tr valign="top"> 
          <th scope="row">image width</th> 
          <td>
            <input name="lastfm-imgwidth" type="text" id="lastfm-imgwidth" value="<?php echo $options['imgwidth']; ?>" size="10" /><br />
            The width of the images
          </td>
        </tr>
        <tr valign="top"> 
          <th scope="row">error message</th> 
          <td><input name="lastfm-noimages" type="text" id="lastfm-noimages" value="<?php echo $options['noimages']; ?>" size="40" /><br />
            Text to display when there are no images to display
          </td>
        </tr>
        <!-- 
        <tr valign="top"> 
          <th scope="row">save thumbnails to cache</th> 
          <td>
            <select style="width: 200px;" id="lastfm-localthumbs" name="lastfm-localthumbs">
              <option value="0"<?php if ('0' == $options['localthumbs']) { echo ' selected'; } ?>>No</option>
              <option value="1"<?php if ('1' == $options['localthumbs']) { echo ' selected'; } ?>>Yes</option>
            </select><br />
            When set to 'yes', thumbnails will be created by the server and saved<br />
            to the cache folder. The pictures will be less grainy, but it takes<br />
            a little longer when the plugin finds a cd for the first time.<br />
            <br />
            Obviously, setting it to yes takes up more disk space.
          </td>
        </tr>
        -->
        <tr valign="top">
          <th scope="row">Send the author<br />of this plugin<br />some earthly love</th>
          <td>
            <a href="https://www.amazon.com/gp/registry/wishlist/2XZPC0CD6SILM/ref=wl_web/">
              <img src="https://images-na.ssl-images-amazon.com/images/G/01/gifts/registries/wishlist/v2/web/wl-btn-129-b._V52198553_.gif" width="129" alt="My Amazon.com Wish List" height="42" />
            </a>
          </td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" name="lastfm-submit" value="Update Options &raquo;" />
      </p>
    </fieldset>
  </form>
<?php
    # missing cd covers?
    $_missing = $this->getmissingcovers();
    if ($_missing) {
?>

  <script type="text/javascript">
  //<![CDATA[
    function showupload(i) {
      var el = document.getElementById('upload' + i);
      if (el) {
        if ('none' == el.style.display) {
          el.style.display = '';
        } else {
          el.style.display = 'none';
        }
      }
      return false;
    }
  //]]>
  </script>

  <h2>Upload Missing Covers</h2>
  <table class="optiontable"> 
<?php echo $_missing; ?>
  </table>
  <br /><br /><br />
<?php
    }
?>
</div>
<?php
  }

  private function getmissingcovers() {

  	# not available in version 1.4
  	return false;

    $result = "";
    $count = 0;
    $_dir = $this->cachedir();
    if ($handle = @opendir($_dir)) {
      while (false !== ($_file = readdir($handle))) {
        # look for albumdata that have noimage specified
        if ("albumdata" == substr($_file, -9)) {
      	  # what's the artist and title?
      	  # get the correct part of the filename
          $_ta = basename($_file, ".albumdata");
      	  # decode it
      	  $_ta = base64_decode($_ta);
      	  # split it to find artist and title
      	  $_ta = explode('#_#', $_ta);
          $_i = unserialize(file_get_contents($_dir . $_file));
          if ('noimage' == $_i) {
            $count++;

            $result .= '    <tr valign="top">' . "\n";
            $result .= '      <th scope="row">' . urldecode($_ta[0]) . "</th>\n";
            $result .= '      <td>' . "\n";
            $result .= '        <a target="_blank" href="http://images.google.com/images?hl=en&q=+%22' . $_ta[0] . '%22+%22' . $_ta[1] . '%22">[find image]</a>&nbsp;&nbsp;&nbsp;<a href="#" onclick="return showupload(' . $count . ');">' . urldecode($_ta[1]) . "</a><br /><br />\n";
            $result .= '        <span id="upload' . $count . '" style="display: none;">' . "\n";
            $result .= '          <form enctype="multipart/form-data" method="post" action="' . $_SERVER['PHP_SELF'] . '?page=' . strtolower(basename(__FILE__)) . '">' . "\n";
            $result .= '            <input type="hidden" name="ua" value="' . $_ta[0] . '" />' . "\n";
            $result .= '            <input type="hidden" name="ut" value="' . $_ta[1] . '" />' . "\n";
            $result .= '            <input type="file" name="uf" value="" />' . "\n";
            $result .= '            <input type="submit" name="submit" value="upload" />' . "\n";
            $result .= '          </form>' . "\n";
            $result .= '        </span>' . "\n";
            $result .= '      </td>' . "\n";
            $result .= "    </tr>\n";
          }
        }
      }
    }

    if ($result) {
      return $result;
    } else {
  	  return false;
    }
  }

  public function stylesheet() {
    $options = get_option('lastfm-records');
    if ('0' == $options['stylesheet']) {
      return false;
    }
?>
  <!-- added by plugin Last.Fm Records -->
  <style type="text/css">
    #lastfmrecords    { padding: 0px; padding-bottom: 10px; }
    #lastfmrecords li { list-style-type: none; margin: 0px; padding: 0px; display: inline; }
<?php
  # people using their own class in css?
  if (0 != intval($options['imgwidth'])) {
?>
    img.cdcover       { height: <?php echo $options['imgwidth'] ?>px; width: <?php echo $options['imgwidth'] ?>px; margin: 0px 5px 5px 0px; border: 0px; }
<?php
      }
?>
  </style>
<?php
  }

  private function log($text) {
    if (DEBUG) {
      file_put_contents(
        dirname(__FILE__) . '/lastfmrecords.log',
        date("y-m-j h:i:s ") . $text . "\n",
        FILE_APPEND);
    }
  }
}

# this function gets called when widgets are supported
function widget_lastfmrecords_init() {

  # does this wordpress environment support widgets?
  if (!function_exists('register_sidebar_widget'))
    return;

  # output for sidebar
  function widget_lastfmrecords($args) {
    extract($args);

    $options = get_option('lastfm-records');

    echo "\n\n" . $before_widget . $before_title . $options['title'] . $after_title . "\n";
    $lfm = new LastFmRecords();
    $lfm->display();
		echo $after_widget . "\n\n";
  }

  function widget_lastfmrecords_control() {
    $options = get_option('lastfm-records');

    if (($_POST['lastfmrecords-submit']) && ("" != $_POST['lastfmrecords-title'])) {
      $options['title'] = strip_tags(stripslashes($_POST['lastfmrecords-title']));
      update_option('lastfm-records', $options);
    }

    $title = htmlspecialchars($options['title'], ENT_QUOTES);
?>
    <p style="text-align:right;">
      <label for="lastfmrecords-title">title: 
        <input style="width: 200px;" id="lastfmrecords-title" name="lastfmrecords-title" type="text" value="<?php echo $title ?>" />
      </label>
    </p>
    <p>Other options are on the <a href="<?php echo lastfmrecords_wordpressurl(); ?>wp-admin/plugins.php?page=<?php echo strtolower(basename(__FILE__)); ?>">options page</a> for this plugin.</p>
    <input type="hidden" id="lastfmrecords-submit" name="lastfmrecords-submit" value="1" />    
<?php
  }

  # if you want to use it as a widget, it's available
  register_sidebar_widget('Last.Fm Records', 'widget_lastfmrecords');

  // and we need a small form to add a title in the sidebar
  register_widget_control('Last.Fm Records', 'widget_lastfmrecords_control', 375, 95);
}

# one place were we can change the settings
add_action('admin_menu', 'lastfmrecords_add_pages');

# add stylesheet(s) to head
add_action('wp_head', 'lastfmrecords_stylesheet');

# widget variant
add_action('plugins_loaded', 'widget_lastfmrecords_init');

# to display cd covers on a page
add_filter('the_content', 'lastfmrecords_filtercontent');

#####################################################################
# some functions to help Wordpress get into the LastFmRecords class #
#####################################################################

function lastfmrecords_display($period = false, $count = false) {
  $lfm = new LastFmRecords();
  $lfm->display($period, $count);
}

function lastfmrecords_wordpressurl() {
  $lfm = new LastFmRecords();
  return $lfm->wordpressurl();
}

function lastfmrecords_add_pages() {
  if (function_exists('add_options_page')) {
    # Lorelle told me to put it on the plugins tab
    add_submenu_page('plugins.php', 'Last.Fm Records', 'Last.Fm Records', 8, basename(__FILE__), 'lastfmrecords_options_page');
  }
}

function lastfmrecords_options_page() {
  $lfm = new LastFmRecords();
  $lfm->options_page();
}

function lastfmrecords_stylesheet() {
  $lfm = new LastFmRecords();
  $lfm->stylesheet();
}

function lastfmrecords_filtercontent($content) {
  $lfm = new LastFmRecords();
  return $lfm->filtercontent($content);
}

?>