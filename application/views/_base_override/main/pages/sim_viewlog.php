<?php if (isset($next) || isset($prev)): ?>
	<div class="float_right">
		<?php if (isset($prev)): ?>
			<?php echo anchor('sim/viewlog/'. $prev, img($images['prev']), array('class' => 'image'));?>
		<?php endif; ?>

		<?php if (isset($next)): ?>
			<?php echo anchor('sim/viewlog/'. $next, img($images['next']), array('class' => 'image'));?>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php echo text_output($title, 'h1', 'page-head');?>

<p><?php echo link_to_if($edit_valid, 'manage/logs/edit/'. $id, $label['edit'], array('class' => 'edit fontSmall bold'));?></p>

<p class="fontSmall bold gray">
	<?php echo $label['posted'] .' '. $date;?>
	<?php echo $label['by'] .' '. $author;?>

	<?php if (isset($update)): ?>
		<br />
		<?php echo $label['edited'] .' '. $update;?><br />
	<?php endif;?>
</p>

<p class="fontSmall gray">
	<?php if (!empty($location)): ?>
		<strong><?php echo $label['location'] .'</strong> '. $location;?>
	<?php endif; ?>

	<?php if (!empty($stardate)): ?>
		<br /><strong><?php echo $label['stardate'] .'</strong> '. $stardate;?>
	<?php endif; ?>
</p>

<?php echo text_output($content);?>

<p>&nbsp;</p>

<?php if (isset($next) || isset($prev)): ?>
	<div class="float_right">
		<?php if (isset($prev)): ?>
			<?php echo anchor('sim/viewlog/'. $prev, img($images['prev']), array('class' => 'image'));?>
		<?php endif; ?>

		<?php if (isset($next)): ?>
			<?php echo anchor('sim/viewlog/'. $next, img($images['next']), array('class' => 'image'));?>
		<?php endif; ?>
	</div>
<?php endif; ?>

<p class="fontSmall gray">
	<?php if (isset($tags)): ?>
		<br /><strong><?php echo $label['tags'] .'</strong> '. $tags;?>
	<?php endif; ?>
</p>

<p><?php echo anchor('feed/logs', img($images['feed']), array('class' => 'image'));?></p>

<?php if ($this->auth->is_logged_in() === TRUE): ?>
	<p class="bold">
		<a href="#" id="add_comment" rel="facebox" myID="<?php echo $id;?>" class="image">
			<?php echo img($images['comment']) .' '. $label['addcomment'];?>
		</a>
	</p>
<?php endif; ?>

<?php if (isset($comments) && is_array($comments)): ?>
	<h2 class="gray"><?php echo $label['comments'] . ' (' . $comment_count . ')';?></h2>

	<div id="comments">
	<?php foreach ($comments as $value): ?>
		<p>
			<strong>
				<?php echo ucfirst($label['by']) . ' ' . $value['author'];?>
				<?php echo $label['on'] . ' ' . $value['date'];?>
			</strong><br /><br />
			<?php echo text_output($value['content'], '');?>
		</p>
	<?php endforeach; ?>
	</div>
<?php endif; ?>