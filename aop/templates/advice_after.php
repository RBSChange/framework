<?php echo $originalMethodModifiers; ?> function <?php echo $originalMethodName; ?>(<?php echo $originalParameters; ?>)
{
	<?php echo $adviceParameters; ?>
	try
	{
		$_returnValue = <?php echo $originalCallOp; ?><?php echo $originalMethodName; ?>_replaced<?php echo $replacedCount; ?>(<?php echo $originalParametersCall; ?>);
		$_hasThrowed = false;
		<?php echo $adviceCode; ?> 
		return $_returnValue;
	}
	catch (Exception $_exception)
	{
		$_hasThrowed = true;
		<?php echo $adviceCode; ?> 
		throw $_exception;
	}
}

/**
 * @see <?php echo $originalMethodName; ?> 
 */
private <?php echo $originalStatic ?>function <?php echo $originalMethodName; ?>_replaced<?php echo $replacedCount; ?>(<?php echo $originalParameters; ?>)
{
	<?php echo $originalMethodBody; ?> 
}