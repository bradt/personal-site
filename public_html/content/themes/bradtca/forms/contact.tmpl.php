<?php if ( $form->success ) : ?>

<h3>Success!</h3>

<p>Your email has been delivered to my inbox and you can expect a reply within 24 hours.</p>

<?php else : ?>

<?php if ( !empty( $form->errors ) ) : ?>
<p class="error-msg">Please correct the errors below.</p>
<?php endif; ?>

<?php
        $form->display();

    $hire_links = '
    <a href="http://www.wphired.com">WPhired</a>,
    <a href="http://jobs.freelanceswitch.com">Freelance Switch</a>,
    <a href="http://odesk.com">oDesk</a>, or
    <a href="http://jobs.wordpress.net/">jobs.wordpress.net</a>
';
?>
<?php /*
<div class="section section-work"<?php echo ($_POST['what'] != 'work') ? ' style="display: none;"' : ''; ?>>
    <h3>Work, job, or contract</h3>

    <?php
    $months_5 = date( 'F', strtotime( date( 'Y-m-d' ) . " +6 months") );
    $months_6 = date( 'F', strtotime( date( 'Y-m-d' ) . " +7 months") );
    ?>

    <p>
        I'm actually fully booked indefinitely, focusing
        100% on a <a href="http://wpappstore.com/">new business</a> I've
        started. Try contacting the following colleagues of mine who may be
        available to take on your project. Each is well equiped to handle a
        project involving WordPress, web development, and web design.
    </p>

    <h4>Freelancers</h4>

    <ul>
        <li><a href="http://casjam.com">Brian Casel</a> <small>Connecticut, USA</small></li>
        <li><a href="http://www.shawnjohnston.ca">Shawn Johnston</a> <small>Vancouver, Canada</small></li>
    </ul>

    <h4>Halifax</h4>

    <ul>
        <li><a href="http://kulapartners.com/">Kula Partners</a> <small>Jeff White &amp; Carmen Pirie</small></li>
        <li><a href="http://hithere.com/">Hi There</a> <small>Ian Conrad &amp; Nick Brunt</small></li>
        <li><a href="http://hopcreative.com">Hop Creative</a> <small>Trevor Delaney</small></li>
    </ul>

</div>

<div class="section section-schedule"<?php echo ($_POST['what'] != 'work' || $_POST['schedule'] != '1-4') ? ' style="display: none;"' : ''; ?>>
    <h3>Schedule Not Workable</h3>

    <p>I'm usually fully booked with work for 3-4 weeks at any given time.
    So, generally I schedule new projects to start in 3-4 weeks at the earliest.
    If you'd like me to take on your project, you will need to revise your
    schedule.</p>

    <p>If you can't adjust your schedule, you can try hiring through
    <?php echo $hire_links; ?>. However, you'll be extremely lucky to find someone
    with good communication skills and who does quality work that isn't already
    busy.</p>

</div>
*/
?>

<section class="section section-budget entry-content"<?php echo ( !isset( $_POST['what'] ) || $_POST['what'] != 'work' || !isset( $_POST['budget'] ) || $_POST['budget'] != 'too_low' ) ? ' style="display: none;"' : ''; ?>>
    <h3>Budget Not Workable</h3>

    <p>I do not take on projects in this budget range. If you'd like me to take
    on your project, you will need to revise your budget. My article
    "<a href="/archives/what-does-it-cost-to-build-a-web-site/">What does it
    cost to build a web site?</a>" may help you with that.</p>

    <p>If you really can't afford a higher budget, you may find someone to help you at
    <?php echo $hire_links; ?>. At that budget though, don't expect good
    communication skills or quality work.</p>

</section>

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
