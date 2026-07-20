<?php
$db = \Config\Database::connect();
$fields = $db->getFieldNames('users');
echo json_encode($fields);
