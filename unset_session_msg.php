<?php
session_start();

unset($_SESSION['success']);
unset($_SESSION['suc_msg']);

http_response_code(200);