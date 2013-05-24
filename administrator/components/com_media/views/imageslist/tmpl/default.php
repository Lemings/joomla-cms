<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>
<?php if (count($this->images) > 0 || count($this->folders) > 0) { ?>
<ul class="manager thumbnails">
	<?php for ($i = 0, $n = count($this->folders); $i < $n; $i++) :
		$this->setFolder($i);
		echo $this->loadTemplate('folder');
	endfor; ?>

	<?php for ($i = 0, $n = count($this->images); $i < $n; $i++) :
		$this->setImage($i);
		echo $this->loadTemplate('image');
	endfor; ?>

	<?php for ($i = 0, $n = count($this->docs); $i < $n; $i++) :

		if (isset($this->docs[$i]))
		{
			$this->_tmp_doc = &$this->docs[$i];
		}
		else
		{
			$this->_tmp_doc = new JObject;
		}

		echo $this->loadTemplate('documents');
	endfor; ?>
</ul>
<?php } else { ?>
	<div id="media-noimages">
		<div class="alert alert-info"><?php echo JText::_('COM_MEDIA_NO_IMAGES_FOUND'); ?></div>
	</div>
<?php } ?>
