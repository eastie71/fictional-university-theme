This email was sent from your website "<?php echo $blogName; ?>" by the Wordfence plugin at <?php echo $date; ?>

The Wordfence administrative URL for this site is: <?php echo network_admin_url('admin.php?page=Wordfence'); ?>

<?php echo $alertMsg; ?>
<?php if($IPMsg){ echo "\n$IPMsg\n"; } ?>

<?php if(! $isPaid){ ?>
NOTE: You are using the free version of Wordfence. Upgrade today:
 - Advanced features like IP reputation monitoring, country blocking, an advanced comment spam filter and cell phone sign-in give you the best protection available
 - Remote, frequent and scheduled scans
 - Access to Premium Support
 - Discounts of up to 90% for multiyear and multi-license purchases

Click here to upgrade to Wordfence Premium:
https://www.wordfence.com/zz1/wordfence-signup/
<?php } ?>

--
To change your alert options for Wordfence, visit:
<?php echo $myOptionsURL; ?>

To see current Wordfence alerts, visit:
<?php echo $myHomeURL; ?>



