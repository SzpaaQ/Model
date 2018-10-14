<?php

/**
 * 
 * LICENCE
 * ALL RIGHTS RESERVED.
 * YOU ARE NOT ALLOWED TO COPY/EDIT/SHARE/WHATEVER.
 * IN CASE OF ANY PROBLEM CONTACT AUTHOR.
 * @author    Łukasz Szpak (szpaaaaq@gmail.com)
 * @Copyright 2018 SzpaQ
 * @license   ALL RIGHTS RESERVED
 *
 * * */


if(!defined('PATH')) {
	define('PATH', __DIR__ .'/');
}
if(!defined('MODEL_PATH')) {
	define('MODEL_PATH', __DIR__ .'/Models/');
}






function auto($class_name) 
{
	
	try
	{
		
		if(!class_exists($class_name))
		{
			
			$file_path = PATH.'classes/'. str_replace('\\', '/', $class_name) .'.php';
			
			if(file_exists($file_path)) 
			{
								
				require $file_path;
				
				return;
				
			}
			
			$file_path = MODEL_PATH.'/'. str_replace('\\', '/', $class_name) .'.php';
			
			if(file_exists($file_path)) 
			{
				
				require $file_path;
				
				return;
				
			}
			
				
		}

		
	}
	catch(Exception $e)
	{
		exit($e->getMessage());
	
	}

}

spl_autoload_register('auto');
