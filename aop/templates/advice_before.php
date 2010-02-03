<?php echo $originalMethodModifiers; ?> function <?php echo $originalMethodName; ?>(<?php echo $originalParameters; ?>)
{
	<?php echo $adviceParameters; ?>
	<?php echo $adviceCode; ?> 
	return <?php echo $originalCallOp; ?><?php echo $originalMethodName; ?>_replaced<?php echo $replacedCount; ?>(<?php echo $originalParametersCall; ?>);
}

/**
 * @see <?php echo $originalMethodName; ?> 
 */
private <?php echo $originalStatic ?>function <?php echo $originalMethodName; ?>_replaced<?php echo $replacedCount; ?>(<?php echo $originalParameters; ?>)
{
	<?php echo $originalMethodBody; ?> 
}