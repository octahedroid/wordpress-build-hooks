<?php

/**
 * Plugin Name: Build Hooks
 * Description: This module allows you to trigger a build hook on any service provider that supports build hooks.
*/

require_once __DIR__.'/../../../../vendor/autoload.php';

add_action( 'admin_menu', 'register_admin_page' );

function register_admin_page() {
  add_menu_page(
    'Build Hooks',
    'Build Hooks', 
    'manage_options', 
    'build-hooks', 
    'build_hooks'
  );
}

if (isset($_POST['action'])) {
  if ($_POST['action'] === 'trigger_build') {
    trigger_build();
  }
}

function build_hooks() {

  $site_url = site_url('');
  $token = _getSecret('CIRCLE_CI_TOKEN');
  
  echo <<<EOF
<div class="wrap">
  <h1>Build Hooks</h1>
  ​<hr />
  <h2>CircleCI options</h2>
  <strong>Token:</strong> $token
  <br />
  <form method="post" action="$site_url/wp-admin/admin.php?page=build-hooks" novalidate="novalidate">
    <div class="submit">
      <input name="action" value="trigger_build" type="hidden">
      <input name="submit" id="submit" class="button button-primary" value="Trigger Build" type="submit">
    </div>
  </form>
</div>
EOF;
}

function _getSecret($tokenName) {
  define( 'TERMINUS_SECRETS', WP_CONTENT_DIR.'/uploads/private/secrets.json' );
  $json = file_get_contents(TERMINUS_SECRETS);
  $json_data = json_decode($json, true);

  return $json_data[$tokenName];
}

function _getEnv() {
  if (!empty($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] !== 'lando') {
    return $_ENV['PANTHEON_ENVIRONMENT'];
  }

  // @TODO return master, live, prod or the default stage name.
  return 'dev';
}

function trigger_build() {

  $buildMetadata = file_get_contents(__DIR__.'/../../../../build-metadata.json');
  $metadata = json_decode($buildMetadata, true);

  $token = _getSecret('CIRCLE_CI_TOKEN');

  $branch = $metadata['ref'];
  $environment = _getEnv();
  $site = 'https://'.$environment.'-'.$_ENV['PANTHEON_SITE_NAME'].'.pantheonsite.io/wp';
  $url = 'https://circleci.com/api/v1.1/project/gh/octahedroid/pantheon-proxy-wordpress/tree/'.$branch.'?circle-token='.$token;
  
  $client = new \GuzzleHttp\Client([
    'headers' => [ 'Content-Type' => 'application/json' ]
  ]);

  $response = $client->post(
    $url,
    [
      'json' => [
        'build_parameters' => [
          'CIRCLE_JOB' => 'trigger_build_deploy_static',
          'WORDPRESS_GRAPHQL' => $site,
          'TERMINUS_ENV' => $environment
        ]
      ]
    ]
  );  
}

function set_option($option_name, $option_value) {
  if (get_option($option_name) !== false) {
      update_option($option_name, $option_value);
      retturn;
  }

  add_option($option_name, $option_value, null, 'no');
}
