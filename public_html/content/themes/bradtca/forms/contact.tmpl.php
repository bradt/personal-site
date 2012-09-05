<?php if ($form->success) : ?>

<h3>Success!</h3>

<p>Your email has been delivered to my inbox and you can expect a reply within 24 hours.</p>

<?php else : ?>

<p class="indicates"><span class="req">*</span> Indicates required fields</p>

<?php if (!empty($form->errors)) : ?>
<p class="error-msg">Please correct the errors below.</p>
<?php endif; ?>

<?php
$form->display();

$hire_links = '
    <a href="http://wpcandy.com/pros">WP Candy</a>,
    <a href="http://odesk.com">oDesk</a>, or
    <a href="http://jobs.wordpress.net/">jobs.wordpress.net</a>
';
?>

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

<?php
/*
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

<div class="section section-budget"<?php echo ($_POST['what'] != 'work' || $_POST['budget'] != 'too_low') ? ' style="display: none;"' : ''; ?>>
    <h3>Budget Not Workable</h3>
    
    <p>I do not take on projects in this budget range. If you'd like me to take
    on your project, you will need to revise your budget. My article
    "<a href="/archives/what-does-it-cost-to-build-a-web-site/">What does it
    cost to build a web site?</a>" may help you with that.</p>
    
    <p>If you really can't afford a higher budget, you may find someone to help you at
    <?php echo $hire_links; ?>. At that budget though, don't expect good
    communication skills or quality work.</p>

</div>

<div class="section section-personal"<?php echo ($_POST['what'] != 'personal') ? ' style="display: none;"' : ''; ?>>
    <h3>Help with an existing web site</h3>
    
    <p>Unfortunately, I do not take on small projects like these. I used to when
    I first started freelancing, but I now only take on larger projects (3+
    weeks of work). I recommend submitting
    your question to <a href="http://www.quora.com/">Quora</a> or
    <a href="http://webmasters.stackexchange.com/">Pro Webmasters</a>. If the
    question concerns WordPress, you should use the
    <a href="http://wordpress.org/support/">WordPress.org Support</a> forums or
    <a href="http://wordpress.stackexchange.com/">WordPress Answers</a>.</p>
    
    <p>If you have an ongoing need, I recommend hiring a professional from
    <?php echo $hire_links; ?>.</p>
.</p>
</div>
*/
?>

<div class="section section-plugin"<?php echo ($_POST['what'] != 'plugin') ? ' style="display: none;"' : ''; ?>>
    <h3>WordPress plugin support</h3>
    
    <p>Unfortunately, I cannot afford the time to provide email support for my
    WordPress plugins.</p>
    
    <p>I am subscribed to each of the following WordPress.org support forums
    however, and will reply to your posting eventually.</p>
    
    <ul>
        <li><a href="http://wordpress.org/tags/live-comment-preview">Live Comment Preview</a></li>
        <li><a href="http://wordpress.org/tags/twitter-importer">Twitter Importer</a></li>
        <li><a href="http://wordpress.org/tags/wp-migrate-db">WP Migrate DB</a></li>
    </ul>
    
    <p>
        You could also try <a
        href="http://wordpress.stackexchange.com/">WordPress Answers</a> at
        Stack Exchange.
    </p>
    
    <p>If it's an emergency, you should contact your web designer or web developer
    to assist you in resolving the issue. You could also hire a professional from
    <?php echo $hire_links; ?>.</p>
    
</div>

<input id="contactsubmit" class="button" type="image" value="Submit" src="<?php bloginfo('template_url'); ?>/images/blank.gif" name="Submit"/>

<?php endif; ?>
