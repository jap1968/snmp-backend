<?php
// Routes

define('HTTP_STATUS_200', 200); // OK
define('HTTP_STATUS_401', 401); // Unauthorized
define('HTTP_STATUS_500', 500); // Internal Server Error
// https://stackoverflow.com/questions/1434315/http-status-code-for-database-is-down
define('HTTP_STATUS_503', 503); // Service Unavailable

// *****************************************************************************

define('JSON_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
// define('JSON_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);


$app->group('/api', function () {
  // 20250526 - Send authentication parameters as payload
  // curl \
  // --header "Content-Type: application/json" \
  // --request POST \
  // --data <json> \
  // <url>
  $this->group('/1', function () { // API version
    $this->get('/test', function ($request, $response, $args) {

      $tIni = microtime(true);
      $respBody = array();

      $respBody['version'] = array(
        'php' => phpversion(),
        'slim' => Slim\App::VERSION
      );

      $dt = new DateTime('now', new DateTimeZone('Europe/Madrid'));
      $respBody['serverTime'] = $dt->format('c');

      $tFin = microtime(true);
      $dFin = number_format(1000 * ($tFin - $tIni), 1);
      $respBody['totalTime'] = "{$dFin} ms";

      return $response->withJson($respBody, HTTP_STATUS_200, JSON_OPTIONS);

    });

    $this->group('/snmp', function () {
      require_once '../src/app/snmp.php';

      // Walk itera un conjunto de valores: interfaces, direcciones MAC, o cualquier cosa no categorizada
      $this->post('/{snmpVersion}/walk/{agent}/{oid}', function ($request, $response, $args) {
        $this->logger->info("POST", []);

        $snmpVersion = $request->getAttribute('snmpVersion');
        $agent = $request->getAttribute('agent');
        $oid = $request->getAttribute('oid');
        // 20250523
        // $headers = $request->getHeaders();
        // $strRequest = print_r((array) $request));
        $payload = $request->getBody();
        $this->logger->info("Payload: ", [$payload]);
        $authParams = json_decode($payload, true);

        $h = new Jap\Network\Snmp($this);
        $res = $h->snmpWalk($snmpVersion, $agent, $authParams, $oid);

        $respStatus = $res === false ? HTTP_STATUS_500 : HTTP_STATUS_200;
        return $response->withJson($res, $respStatus, JSON_OPTIONS);
      });


      // http://www.slimframework.com/docs/v3/objects/router.html#optional-segments
      $this->post('/{snmpVersion}/get/{agent}/{oids:.*}', function ($request, $response, $args) {
        // $community = $request->getAttribute('community');

        $snmpVersion = $request->getAttribute('snmpVersion');
        $agent = $request->getAttribute('agent');
        $payload = $request->getBody();
        $this->logger->info("Payload: ", [$payload]);
        $authParams = json_decode($payload, true);
        $community = $authParams['community'];

        $oids = explode('/', $args['oids']);

        $h = new Jap\Network\Snmp($this);
//        $mib = $h->snmp2get_o($agent, $oids, $outputFormat, $community);
        $res = $h->snmpGet($snmpVersion, $agent, $authParams, $oids);

        $respStatus = $res === false ? HTTP_STATUS_500 : HTTP_STATUS_200;
        return $response->withJson($res, $respStatus, JSON_OPTIONS);
      });

    });
  });
  // *** END 20250526

});
