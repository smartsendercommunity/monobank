<?php

// Данные интеграции с InterKassa
$mono_token = "uvxQgsYST_AbTfPfI0UeRB9htm5tQSLEn1EPycW_uKzU";
$ss_token = "q6lYgUcGt0SCXuqzKHb0DSkw8ZPhTMcQC4UUZxhC7W6q2lzTyZjaNX7VDTwB";

// Сервисные данные
$logUrl = "https://webhook.site/monobank";
$dir = dirname($_SERVER["PHP_SELF"]);
$url = ((!empty($_SERVER["HTTPS"])) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $dir;
$url = explode("?", $url);
$url = $url[0];