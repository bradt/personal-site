<?php if ( $form->success ) : ?>

<h3>Success!</h3>

<p>Your email has been delivered to my inbox and you can expect a reply within 24 hours.</p>

<?php else : ?>

<?php if ( !empty( $form->errors ) ) : ?>
<p class="error-msg">Please correct the errors below.</p>
<?php endif; ?>

<?php $form->display(); ?>

<section class="section section-plugin entry-content"<?php echo ( !isset( $_POST['what'] ) || $_POST['what'] != 'plugin' ) ? ' style="display: none;"' : ''; ?>>
    <h3>WordPress plugin support</h3>

    <p>Unfortunately, I cannot afford the time to provide email support for the
    free plugins I've released at <a href="http://wordpress.org/extend/plugins/">WordPress.org</a>.</p>

    <p>I am subscribed to each of the following WordPress.org support forums
    however, and will reply to your posting eventually.</p>

    <ul>
        <li><a href="http://wordpress.org/support/plugin/wp-migrate-db">WP Migrate DB</a></li>
        <li><a href="http://wordpress.org/support/plugin/amazon-s3-and-cloudfront">Amazon S3 and CloudFront</a></li>
        <li><a href="http://wordpress.org/support/plugin/live-comment-preview">Live Comment Preview</a></li>
        <li><a href="http://wordpress.org/support/plugin/twitter-importer">Twitter Importer</a></li>
    </ul>

    <p>
        You could also try <a
        href="http://wordpress.stackexchange.com/">WordPress Answers</a> at
        Stack Exchange.
    </p>
</section>

<button class="button">Send Email</button>

<?php endif; ?>
