<?php
header('Content-Type: application/json');
$data = file_get_contents('../data/departamentos.json');
echo $data;