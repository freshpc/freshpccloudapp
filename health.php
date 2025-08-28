<?php
require_once 'config.php';
if (isset($pdo) && $pdo) {
    echo "PDO connection is working!";
} else {
    echo "PDO NOT working!";
}