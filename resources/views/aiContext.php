<?php /** @var array{file:string, line:int, class:string, function:string, args:array, args_formatted:string} $details */ ?>
Class: <?php echo $details['class'] ?>
File: <?php echo $details['file'] ?>
Line: <?php echo $details['line'] ?>
Function Name: <?php echo $details['function'] ?>
Arguments: <?php echo $details['args_formatted'] ?>
<?php if (!empty($details['code'])): ?>
Code:
<?php echo $details['code'] ?>
<?php endif ?>