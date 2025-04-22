<?php
session_start();
session_destroy();
header("Location: /InterfazLogin/FuncionLogin/login.html");
exit();
?>