<?php
// includes/db.php
// Reuses the same bootstrap.php that index.php already loads, so the API
// shares one DB connection setup with the website — no duplicated credentials.
//
// This file lives at:
//   C:\xampp\htdocs\croven-labs-apps\apps\countdowns\api\includes\db.php
// bootstrap.php lives at:
//   C:\xampp\htdocs\croven-labs-apps\config\bootstrap.php
// (same relative location index.php uses: dirname(__DIR__, 2) from
// apps/countdowns/index.php — from api/includes/ that's one level deeper,
// hence dirname(__DIR__, 4) here.)

require_once dirname(__DIR__, 4) . '/config/bootstrap.php';

// $pdo now exists, provided by bootstrap.php — nothing else to do here.
