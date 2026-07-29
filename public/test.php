<?php

// Development only — phpinfo is an information disclosure vulnerability in production.
if (! in_array(app()->environment(), ['local', 'testing'], true)) {
    http_response_code(404);
    exit;
}

phpinfo();
