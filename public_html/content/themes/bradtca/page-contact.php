<?php
/* Template Name: Contact */

include(BT_FORMS . '/contactform.php');
$form = new ContactForm();

the_post();

if (isset($_GET['ajax'])) :
	
	include(BT_FORMS . '/contact.tmpl.php');
	
else :

get_header();
?>

<section class="page page-contact">

	<h1 class="page-title"><?php the_title(); ?></h1>

	<div class="intro">
		<?php the_content("more..."); ?>
	</div>

	<div class="post">

		<form action="/contact/" method="post">

			<?php include(BT_FORMS . '/contact.tmpl.php'); ?>
			
		</form>

	</div>

</section>

<?php
get_footer();

endif;
?>
