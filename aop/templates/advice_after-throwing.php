<?php echo $originalMethodModifiers; ?> function <?php echo $originalMethodName; ?>(<?php echo $originalParameters; ?>)
{
	try
	{
		return <?php echo $originalCallOp; ?><?php echo $originalMethodName; ?>_replaced<?php echo $replacedCount; ?>(<?php echo $originalParametersCall; ?>);
	}
	catch (<?php echo $exceptionToCatch; ?> $_exception)
	{
		<?php echo $adviceParameters; ?>
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