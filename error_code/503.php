<?php
http_response_code(503);
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="5;url=<?php echo BASE_URL?>">
    <title>Service Unavailable</title>
</head>
<body>
    <h1>🚧 Service Temporarily Unavailable</h1>
    <p>Redirecting in 5 seconds...</p>
</body>
</html>