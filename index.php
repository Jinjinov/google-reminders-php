<!DOCTYPE html>
<html>
  <head>
  </head>
  <body>
    <?php
    require_once __DIR__.'/vendor/autoload.php';

    require 'reminders.php';

    session_start();

    $client = new Google_Client();
    $client->setAuthConfig('client_secret.json');

    $client->addScope('https://www.googleapis.com/auth/reminders');

    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
      // Set your method for authentication. Depending on the API, 
      // This could be directly with an access token or API key.
      $client->setAccessToken($_SESSION['access_token']);

      // returns a Guzzle HTTP Client
      $httpClient = $client->authorize();

      $reminders = list_reminders($httpClient, 10);

      foreach($reminders as $reminder) {
        echo '<p>';
        echo $reminder;
        echo '</p>';
      }
    }
    else {
      $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
      header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
    }
    ?>
  </body>
</html>