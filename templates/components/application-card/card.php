<?php include_once 'logic-preparation.php' ?>

<div class="<?= $cardClass ?>" data-app-id="<?= $app['id'] ?>">
	
	<?php include_once 'card-application-header.php' ?>
    
	<?php include_once 'card-application-user.php' ?>
    
	<?php include_once 'card-application-status.php' ?>
    
    <p class="app-message"><?= nl2br(e($app['message'])) ?></p>
    
	<?php include_once 'card-application-relevant-until.php' ?>
	
	<?php include_once 'card-application-images.php' ?>
    
	<?php include_once 'card-application-contacts.php' ?>
    
	<?php include_once 'card-application-footer.php' ?>

</div>
