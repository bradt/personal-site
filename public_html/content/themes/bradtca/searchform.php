<?php
/*
<form method="get" id="searchform" action="<?php bloginfo('url'); ?>/">
<div><input type="text" value="<?php the_search_query(); ?>" name="s" id="s" />
<input type="submit" id="searchsubmit" value="Search" />
</div>
</form>
*/
?>

<form action="http://www.google.ca/cse" id="cse-search-box">
  <div>
    <input type="hidden" name="cx" value="partner-pub-0807380235473840:h77qtewh0ug" />
    <input type="hidden" name="ie" value="UTF-8" />
    <input type="text" name="q" size="31" class="text" />
    <input type="submit" name="sa" value="Search" class="button" />
  </div>
</form>
<script type="text/javascript" src="http://www.google.com/coop/cse/brand?form=cse-search-box&amp;lang=en"></script>
