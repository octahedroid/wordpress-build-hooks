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
  if ($_POST['action'] === 'update_option_circleci_page') {
    setOptionsPantheon($_POST);
  }

  if ($_POST['action'] === 'trigger_build') {
    trigger_build();
  }
}

function setOptionsPantheon($data){
  if(!empty($data['circleci'])){
    foreach($data['circleci'] as $key => $option) {
      $option_name = '_pantheon_circleci_' . $key ;
      
      $new_value = is_array($option) ? serialize($option) : stripslashes($option);
      if (get_option( $option_name ) !== false) {
          // The option already exists, so we just update it.
          update_option($option_name, $new_value);
      } else {
          // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
          $deprecated = null;
          $autoload = 'no';
          add_option($option_name, $new_value, $deprecated, $autoload);
      }
    }
  }
  
  if(!isset($data['circleci']['token']) AND get_option( '_pantheon_circleci_token' ) !== false){
    update_option('_pantheon_circleci_token', '');
  }
}

function build_hooks() {

  $site_url = site_url('');
  $token = get_option('_pantheon_circleci_token');
  
  echo <<<EOF
<div class="wrap">
  <h1>Build Hooks</h1>
  <h2>CircleCI options</h2>
  ​<hr />
  <form method="post" action="$site_url/wp-admin/admin.php?page=build-hooks" novalidate="novalidate">
    <table class="form-table">
      <tbody>
                <tr>
                    <th scope="row">Token</th>
          <td> 
            <fieldset>
              <legend class="screen-reader-text">Token</legend>
                <input type="text" class="full-input" name="circleci[token]" value="$token" size="64">
            </fieldset>
          </td>
        </tr>
      </tbody>
    </table>
  ​
      <div class="submit">
          <input name="action" value="update_option_circleci_page" type="hidden">
          <input name="submit" id="submit" class="button button-primary" value="Save changes" type="submit">
      </div>
  </form>

  <h2>CircleCI Build</h2>
  ​<hr />
  <form method="post" action="$site_url/wp-admin/admin.php?page=build-hooks" novalidate="novalidate">
    <div class="submit">
      <input name="action" value="trigger_build" type="hidden">
      <input name="submit" id="submit" class="button button-primary" value="Trigger Build" type="submit">
    </div>
  </form>
</div>
EOF;
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

  $token = get_option('_pantheon_circleci_token');

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
