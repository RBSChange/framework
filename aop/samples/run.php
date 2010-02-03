<?php
require_once("../AOP.php");

require_once("classes.php");
require_once("advices.php");
require_once("classes_replacement.php");

$aop = new f_AOP();

echo "\n#### Before ####\n";
$aop->applyBeforeAdvice("f_aop_samples_amodule_AClass", "getInstance", "f_aop_samples_BeforeAdvice", "log");

echo "\n#### AfterReturning ####\n";
$aop->applyAfterReturningAdvice("f_aop_samples_amodule_AClass", "getInstance", "f_aop_samples_AfterReturningAdvice", "log");

echo "\n#### Before ####\n";
$aop->applyBeforeAdvice("f_aop_samples_amodule_AClass", "save", "f_aop_samples_BeforeAdvice", "log");

echo "\n#### AfterReturning ####\n";
$aop->applyAfterReturningAdvice("f_aop_samples_amodule_AClass", "save", "f_aop_samples_AfterReturningAdvice", "log");

echo "\n#### After ####\n";
$aop->applyAfterAdvice("f_aop_samples_amodule_AClass", "save", "f_aop_samples_AfterAdvice", "log");

echo "\n#### AfterThrowing ####\n";
$aop->applyAfterThrowingAdvice("f_aop_samples_amodule_AClass", "save", "f_aop_samples_AfterThrowingAdvice", "recover", "AnException");
echo "\n#### Around ####\n";
$aop->applyAroundAdvice("f_aop_samples_amodule_AClass", "save", "f_aop_samples_ArroundAdvice", "save");
echo "\n#### Replacement ####\n";
echo $aop->replaceClass("f_aop_samples_AnOtherClass", "f_aop_samples_AnOtherClassReplacement");