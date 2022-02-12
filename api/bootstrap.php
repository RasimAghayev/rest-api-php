<?php
/**
 * Run bootstrap
 */
require_once dirname(__DIR__)."/vendor/autoload.php";
set_exception_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

/**
 * Load .env file
 */
$dotenv=\Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/**
 * header JSON
 */
header("Content-type: application/json; charset=UTF-8");