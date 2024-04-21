<?php

/**
 * Load any .env file. See /.env.example.
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
