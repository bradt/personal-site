<?php // Do not delete these lines
if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not load this page directly. Thanks!');

if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_'.$cookiehash] != $post->post_password) {  // and it doesn't match the cookie
			?>

				<p class="nocomments"><?php _e("This post is password protected. Enter the password to view comments."); ?><p>

				<?php
				return;
		}
}
?>

<div id="comments">

<?php if ('open' == $post-> comment_status) : ?>

	<?php if ($comments) : $i = 0; ?>
		
		<ol class="commentlist">
		
			<?php foreach ($comments as $comment) : $i++ ?>

				<?php
				$gravatar_url = 'http://www.gravatar.com/avatar.php?gravatar_id=' . md5(trim($comment->comment_author_email));
				$gravatar_url .= '&amp;default=' . urlencode(get_bloginfo('template_url') . '/images/gravatar.png');
				?>
				
				<li>
					<div class="comment-tail"></div>
					<div class="comment" id="comment-<?php comment_ID() ?>">
						<div class="gravatar"><img src="<?php echo $gravatar_url; ?>" width="80" height="80" class="avatar" alt="" /></div>
	
						<h1 class="author"><?php comment_author_link() ?></h1>
						<?php comment_text() ?>
						<p class="date">
							<?php comment_date('F jS, Y \a\t g:ia') ?> 
							<?php edit_comment_link('edit','(',')'); ?>
						</p>
					</div>
				</li>

			<?php endforeach; /* end for each comment */ ?>
			
		</ol>

	<?php else : // this is displayed if there are no comments so far ?>

			<!-- If comments are open, but there are no comments. -->

	<?php endif; ?>
	
	<?php live_preview() ?>

	<?php
	if (isset($_POST['comment'])) {
		?>
		<div id="comment_error">
			<p style="font-weight: bold;">Please complete the rest of the required fields to submit your comment.</p>
		</div>
		<?php
	}
	?>

	<h3>Leave a comment</h3>

	<form action="<?php echo get_settings('siteurl'); ?>/wp-comments-post.php#commentform" method="post" id="comment_form" class="user_form">
	<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />

	<div class="field">
		<label for="author">Name:<?php if ($req) echo ' *'; ?></label>
		<input type="text" name="author" id="author" class="styled" value="<?php echo $comment_author; ?>" size="22" tabindex="1" />
	</div>

	<div class="field">
		<label for="email">E-mail (will not be published):<?php if ($req) echo ' *'; ?></label>
		<input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="22" tabindex="2" />
	</div>

	<div class="field">
		<label for="url">Website:</label>
		<input type="text" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="22" tabindex="3" />
	</div>

	<textarea name="comment" id="comment" class="comment_box" cols="20" rows="7" tabindex="4"><?php 
		if (isset($_POST['comment'])) {
			echo htmlentities($_POST['comment']);
		}
	?></textarea>

	<div class="xhtml"><strong>XHTML:</strong> You can use these tags: <?php echo allowed_tags(); ?></div>

	<?php do_action('comment_form', $post->ID); ?>

	<input name="submit" type="image" src="<?php bloginfo('template_url') ?>/images/blank.gif" id="submit" tabindex="5" value="Submit Comment" class="button" />

	</form>

</div>

<?php endif; // if you delete this the sky will fall on your head ?>
